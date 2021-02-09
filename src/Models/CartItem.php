<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Models;

use Carbon\Carbon;
use Tipoff\Support\Contracts\Models\BookingInterface;
use Tipoff\Support\Contracts\Models\CartItemInterface;
use Tipoff\Support\Contracts\Models\FeeInterface;
use Tipoff\Support\Contracts\Models\RateInterface;
use Tipoff\Support\Contracts\Models\RoomInterface;
use Tipoff\Support\Contracts\Models\SlotInterface;
use Tipoff\Support\Contracts\Models\TaxInterface;
use Tipoff\Support\Contracts\Services\BookingService;
use Tipoff\Support\Contracts\Services\FeeService;
use Tipoff\Support\Contracts\Services\RateService;
use Tipoff\Support\Contracts\Services\RoomService;
use Tipoff\Support\Contracts\Services\TaxService;
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
 * @property int|null creator_id
 * @property int|null updater_id
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

    protected $casts = [];

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

    public function createBooking(): BookingInterface
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

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function getStartAtAttribute()
    {
        return $this->hasSlot() ? $this->getSlot()->getStartAt() : null;
    }

    public function getFormattedStartAttribute()
    {
        return $this->hasSlot() ? $this->getSlot()->getFormattedStart() : null;
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

    public function getSlot(): ?SlotInterface
    {
        if (app()->has(BookingService::class)) {
            return app(BookingService::class)->resolveSlot($this->slot_number);
        }

        return null;
    }

    public function generatePricing(): self
    {
        $this->amount = 0;
        $this->total_fees = 0;
        $this->total_taxes = 0;

        if ($rate = $this->getRate()) {
            $this->amount = $rate->getAmount($this->participants, $this->is_private);
        }

        if ($fee = $this->getFee()) {
            $this->total_fees = $fee->generateTotalFeesByCartItem($this);
        }

        if ($tax = $this->getTax()) {
            $this->total_taxes = $tax->generateTotalTaxesByCartItem($this);
        }

        return $this;
    }

    public function createSlot(): ?SlotInterface
    {
        if ($this->hasSlot()) {
            return app(BookingService::class)->resolveSlot($this->slot_number, true);
        }

        return null;
    }

    public static function makeFromSlot(string $slotNumber, int $participants, bool $isPrivate): self
    {
        /** @var SlotInterface $slot */
        $slot = app(BookingService::class)->resolveSlot($slotNumber);

        return self::make([
            'slot_number' => $slotNumber,
            'participants' => $participants,
            'is_private' => $isPrivate,
            'room_id' => $slot->getRoom()->getId(),
            'rate_id' => $slot->getRate()->getId(),
            'tax_id' => $slot->getTax()->getId(),
            'fee_id' => $slot->getFee()->getId(),
        ]);
    }

    public function getRate(): ?RateInterface
    {
        if (app()->has(RateService::class)) {
            return app(RateService::class)->getRate($this->rate_id);
        }

        return null;
    }

    public function getFee(): ?FeeInterface
    {
        if (app()->has(FeeService::class)) {
            return app(FeeService::class)->getFee($this->fee_id);
        }

        return null;
    }

    public function getRoom(): ?RoomInterface
    {
        if (app()->has(RoomInterface::class)) {
            return app(RoomService::class)->getRoom($this->fee_id);
        }

        return null;
    }

    public function getTax(): ?TaxInterface
    {
        if (app()->has(TaxService::class)) {
            return app(TaxService::class)->getTax($this->fee_id);
        }

        return null;
    }
}
