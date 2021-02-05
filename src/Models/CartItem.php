<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Tipoff\Checkout\Contracts\Model\CartItemInterface;
use Tipoff\Checkout\Contracts\Model\SlotInterface;
use Tipoff\Checkout\Contracts\Service\BookingService;
use Tipoff\Checkout\Contracts\Service\FeeService;
use Tipoff\Checkout\Contracts\Service\RateService;
use Tipoff\Checkout\Contracts\Service\TaxService;

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
 * @property int|null creator_id
 * @property int|null updater_id
 */
class CartItem extends Model implements CartItemInterface
{
    use HasFactory;

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

    protected $casts = [];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->setHold($item->cart->user_id);

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

    public function createBooking()
    {
        $slot = $this->createSlot();
        return app(BookingService::class)->createBooking([
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

    public function getAmountPerParticipant(): int
    {
        return (int) floor($this->amount / $this->participants);
    }

    public function scopeFilter(Builder $query, $filters)
    {
        if (empty($filters)) {
            return $query;
        }

        foreach ($filters as $filterKey => $filterValue) {
            switch ($filterKey) {
                case '':
                    // $query->where('', "");
                    break;
            }
        }

        return $query;
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function getRoomId(): ?int
    {
        return $this->room_id;
    }

    public function getFeeId(): ?int
    {
        return $this->fee_id;
    }

    public function getRateId(): ?int
    {
        return $this->rate_id;
    }

    public function getTaxId(): ?int
    {
        return $this->tax_id;
    }

    public function getStartAtAttribute()
    {
        return $this->getSlot()->getStartAt();
    }

    public function getFormattedStartAttribute()
    {
        return $this->getSlot()->getFormattedStart();
    }

    public function hasHold(): bool
    {
        return $this->getSlot()->hasHold();
    }

    public function getHold(): ?object
    {
        return $this->getSlot()->getHold();
    }

    public function releaseHold(): self
    {
        $this->getSlot()->releaseHold();

        return $this;
    }

    public function setHold(int $userId, ?Carbon $expiresAt = null): self
    {
        $this->getSlot()->setHold($userId, $expiresAt);

        return $this;
    }

    public function hasSlot(): bool
    {
        return ! empty($this->slot_number);
    }

    public function generatePricing(): self
    {
        $this->amount = app(RateService::class)->getAmount($this, $this->participants, $this->is_private);
        $this->total_fees = app(FeeService::class)->generateTotalFeesByCartItem($this);
        $this->total_taxes = app(TaxService::class)->generateTotalTaxesByCartItem($this);

        return $this;
    }

    public function getSlot(): SlotInterface
    {
        return app(BookingService::class)->resolveSlot($this->slot_number);
    }

    public function createSlot(): SlotInterface
    {
        return app(BookingService::class)->resolveSlot($this->slot_number, true);
    }

    public static function makeFromSlot(string $slotNumber, int $participants, bool $isPrivate): self
    {
        $slot = app(BookingService::class)->resolveSlot($slotNumber);

        return self::make([
            'slot_number' => $slotNumber,
            'participants' => $participants,
            'is_private' => $isPrivate,
            'room_id' => $slot->getRoomId(),
            'rate_id' => $slot->getRateId(),
            'tax_id' => $slot->getTaxId(),
            'fee_id' => $slot->getFeeId(),
        ]);
    }

    public function scopeVisibleBy(Builder $query, $user)
    {
        return $query;
    }
}
