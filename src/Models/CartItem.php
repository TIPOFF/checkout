<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Models;

use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Tipoff\Bookings\Models\Booking;
use Tipoff\Checkout\Contracts\Models\CartItemInterface;
use Tipoff\Scheduling\Models\Slot;
use Tipoff\Support\Models\BaseModel;
use Tipoff\Support\Traits\HasCreator;
use Tipoff\Support\Traits\HasPackageFactory;
use Tipoff\Support\Traits\HasUpdater;

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
    use HasCreator;
    use HasUpdater;

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

    public function createBooking(): Booking
    {
        $slot = $this->createSlot();

        return Booking::create([
            'order_id' => $this->cart->order_id,
            'slot_id' => $slot->id,
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
        return $this->hasSlot() ? $this->getSlot()->start_at : null;
    }

    public function getFormattedStartAttribute()
    {
        return $this->hasSlot() ? $this->getSlot()->formatted_start : null;
    }

    public function hasHold(): bool
    {
        return $this->hasSlot() ? $this->getSlot()->hasHold() : false;
    }

    public function getHold(): ?object
    {
        return $this->hasSlot() ? $this->getSlot()->getHold() : null;
    }

    public function releaseHold(): self
    {
        if ($this->hasSlot()) {
            $this->getSlot()->releaseHold();
        }

        return $this;
    }

    public function setHold(int $userId, ?Carbon $expiresAt = null): self
    {
        if ($this->hasSlot()) {
            $this->getSlot()->setHold($userId, $expiresAt);
        }

        return $this;
    }

    public function hasSlot(): bool
    {
        return $this->getSlot() ? true : false;
    }

    public function getSlot(): ?Slot
    {
        return Slot::resolveSlot($this->slot_number);
    }

    public function generatePricing(): self
    {
        // TODO - move to services
        $this->amount = $this->rate->getAmount($this->participants, $this->is_private);
        $this->total_fees = $this->fee->generateTotalFeesByCartItem($this);
        $this->total_taxes = $this->tax->generateTotalTaxesByCartItem($this);

        return $this;
    }

    public function createSlot(): ?Slot
    {
        if ($this->hasSlot()) {
            $slot = $this->getSlot();
            $slot->save();

            return $slot;
        }

        return null;
    }

    public static function makeFromSlot(String $slotNumber, int $participants, bool $isPrivate): self
    {
        // TODO - move to services
        $slot = Slot::resolveSlot($slotNumber);
        $rate = $slot->getRate();
        $tax = $slot->getTax();
        $fee = $slot->getFee();

        return self::make([
            'slot_number' => $slotNumber,
            'participants' => $participants,
            'is_private' => $isPrivate,
            'room_id' => $slot->room_id,
            'rate_id' => $rate->id,
            'tax_id' => $tax->id,
            'fee_id' => $fee->id,
        ]);
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

    public function getFee()
    {
        // TODO: return findModel(FeeInterface::class, $this->fee_id);
        return $this->fee;
    }
}
