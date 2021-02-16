<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Models;

use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Tipoff\Support\Contracts\Bookings\BookingInterface;
use Tipoff\Support\Contracts\Checkout\CartItemInterface;
use Tipoff\Support\Contracts\EscapeRoom\RateInterface;
use Tipoff\Support\Contracts\Fees\FeeInterface;
use Tipoff\Support\Contracts\Scheduling\HoldInterface;
use Tipoff\Support\Contracts\Scheduling\SlotInterface;
use Tipoff\Support\Contracts\Taxes\TaxInterface;
use Tipoff\Support\Models\BaseModel;
use Tipoff\Support\Traits\HasPackageFactory;

/**
 * @property int|null id
 * @property string slot_number
 * @property int participants
 * @property bool is_private
 * @property int amount
 * @property int total_taxes
 * @property int total_fees
 * @property int total_deductions
 * @property Carbon created_at
 * @property Carbon updated_at
 * // Raw Relation ID
 * @property int|null room_id
 * @property int|null rate_id
 * @property int|null fee_id
 * @property int|null tax_id
 */
class CartItem extends BaseModel implements CartItemInterface
{
    use HasPackageFactory;

    protected $fillable = [
        'amount',
        'total_taxes',
        'total_fees',
        'total_deductions',
        'slot_number',
        'participants',
        'is_private',
        'room_id',
        'rate_id',
        'tax_id',
        'fee_id',
    ];

    protected $touches = [
        'cart',
    ];

    protected $casts = [
        'id' => 'integer',
        'participants' => 'integer',
        'is_private' => 'boolean',
        'amount' => 'integer',
        'total_taxes' => 'integer',
        'total_fees' => 'integer',
        'total_deductions' => 'integer',
        'room_id' => 'integer',
        'rate_id' => 'integer',
        'fee_id' => 'integer',
        'tax_id' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (CartItem $item) {
            if ($item->hasSlot()) {
                $item->setHold($item->cart->user_id);
            }

            if (empty($item->total_deductions)) {
                $item->total_deductions = 0;
            }

            $item->generatePricing();
        });

        static::deleting(function ($item) {
            $item->releaseHold();
        });

        static::deleted(function ($item) {
            tap($item->cart, function ($cart) {
                $cart->generatePricing();
                $cart->save();
            });
        });
    }

    public function createBooking(): ?BookingInterface
    {
        if ($bookingInterface = findModelInterface(BookingInterface::class)) {
            $slot = $this->createSlot();

            return $bookingInterface::create([
                'order_id' => $this->cart->order_id,
                'slot_id' => $slot->getId(),
                'participants' => $this->participants,
                'is_private' => $this->is_private,
                'amount' => $this->amount,
                'total_taxes' => $this->total_taxes,
                'total_fees' => $this->total_fees,
                'rate_id' => $this->rate_id,
                'tax_id' => $this->tax_id,
                'fee_id' => $this->fee_id,
            ]);
        }

        return null;
    }

    public function getAmountPerParticipant(): int
    {
        return (int) floor($this->amount / $this->participants);
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        if (empty($filters)) {
            return $query;
        }

        /**
         * TODO - implement or kill
        foreach ($filters as $filterKey => $filterValue) {
            switch ($filterKey) {
                case '':
                    // $query->where('', "");
                    break;
            }
        }
         */

        return $query;
    }

    public function room()
    {
        return $this->belongsTo(app('room'));
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function fee()
    {
        return $this->belongsTo(app('fee'));
    }

    public function rate()
    {
        return $this->belongsTo(app('rate'));
    }

    public function tax()
    {
        return $this->belongsTo(app('tax'));
    }

    public function getStartAtAttribute()
    {
        $slot = $this->getSlot();

        return $slot ? $slot->getStartAt() : null;
    }

    public function getFormattedStartAttribute()
    {
        $slot = $this->getSlot();

        return $slot ? $slot->getFormattedStartAt() : null;
    }

    public function hasHold(): bool
    {
        return $this->getHold() ? true : false;
    }

    public function getHold(): ?HoldInterface
    {
        $slot = $this->getSlot();

        return $slot ? $slot->getHold() : null;
    }

    public function releaseHold(): self
    {
        if ($slot = $this->getSlot()) {
            $slot->releaseHold();
        }

        return $this;
    }

    public function setHold(int $userId, ?Carbon $expiresAt = null): self
    {
        if ($slot = $this->getSlot()) {
            $slot->setHold($userId, $expiresAt);
        }

        return $this;
    }

    public function hasSlot(): bool
    {
        return $this->getSlot() ? true : false;
    }

    public function getSlot(): ?SlotInterface
    {
        if ($slotInterface = findModelInterface(SlotInterface::class)) {
            /** @var SlotInterface $slotInterface */
            return $slotInterface::resolveSlot($this->slot_number);
        }

        return null;
    }

    public function generatePricing(): self
    {
        $rate = $this->getRate();
        $this->amount = $rate ? $rate->getTotalByCartItem($this) : 0;

        $fee = $this->getFee();
        $this->total_fees = $fee ? $fee->getTotalByCartItem($this) : 0;

        $tax = $this->getTax();
        $this->total_taxes = $tax ? $tax->getTotalByCartItem($this) : 0;

        return $this;
    }

    public function createSlot(): ?SlotInterface
    {
        if ($slot = $this->getSlot()) {
            $slot->save();

            return $slot;
        }

        return null;
    }

    public static function makeFromSlot(String $slotNumber, int $participants, bool $isPrivate): ?self
    {
        if ($slotInterface = findModelInterface(SlotInterface::class)) {
            /** @var SlotInterface $slotInterface */
            $slot = $slotInterface::resolveSlot($slotNumber);
            $room = $slot->getRoom();
            $rate = $slot->getRate();
            $tax = $slot->getTax();
            $fee = $slot->getFee();

            return self::make([
                'slot_number' => $slotNumber,
                'participants' => $participants,
                'is_private' => $isPrivate,
                'room_id' => $room ? $room->getId() : null,
                'rate_id' => $rate ? $rate->getId() : null,
                'tax_id' => $tax ? $tax->getId() : null,
                'fee_id' => $fee ? $fee->getId() : null,
            ]);
        }

        return null;
    }

    public function scopeVisibleBy(Builder $query, $user): Builder
    {
        return $query;
    }

    /******************************
     * CartItemInterface Implementation
     ******************************/

    public function getSlotNumber(): ?string
    {
        return $this->slot_number;
    }

    public function getIsPrivate(): bool
    {
        return $this->is_private;
    }

    public function getParticipants(): int
    {
        return $this->participants;
    }

    public function getAmount(): Money
    {
        return Money::ofMinor($this->amount ?? 0, 'USD');
    }

    public function getTotalDeductions(): Money
    {
        return Money::ofMinor($this->total_deductions ?? 0, 'USD');
    }

    public function getTotalFees(): Money
    {
        return Money::ofMinor($this->total_fees ?? 0, 'USD');
    }

    public function getFee(): ?FeeInterface
    {
        /** @var FeeInterface $result */
        $result = findModel(FeeInterface::class, $this->fee_id);

        return $result;
    }

    public function getRate(): ?RateInterface
    {
        /** @var RateInterface $result */
        $result = findModel(RateInterface::class, $this->rate_id);

        return $result;
    }

    public function getTax(): ?TaxInterface
    {
        /** @var TaxInterface $result */
        $result = findModel(TaxInterface::class, $this->tax_id);

        return $result;
    }
}
