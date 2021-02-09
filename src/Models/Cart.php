<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Models;

use Brick\Money\Money;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tipoff\Checkout\Events\BookingOrderProcessed;
use Tipoff\Support\Contracts\Models\CartInterface;
use Tipoff\Support\Contracts\Models\LocationInterface;
use Tipoff\Support\Contracts\Models\PaymentInterface;
use Tipoff\Support\Contracts\Models\UserInterface;
use Tipoff\Support\Contracts\Services\DiscountService;
use Tipoff\Support\Contracts\Services\LocationService;
use Tipoff\Support\Contracts\Services\UserService;
use Tipoff\Support\Contracts\Services\VoucherService;
use Tipoff\Support\Models\BaseModel;
use Tipoff\Support\Traits\HasPackageFactory;

/**
 * @property int|null id
 * @property string code
 * @property int amount
 * @property int total_taxes
 * @property int total_fees
 * @property int total_deductions
 * @property Carbon expires_at
 * @property Carbon created_at
 * @property Carbon updated_at
 * // Raw Relation ID
 * @property int|null user_id
 * @property int|null location_id
 * @property int|null creator_id
 * @property int|null updater_id
 */
class Cart extends BaseModel implements CartInterface
{
    use HasPackageFactory;
    use SoftDeletes;

    /**
     * Voucher type used in partial redemptions.
     */
    const PARTIAL_REDEMPTION_VOUCHER_TYPE_ID = 7;

    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'id' => 'integer',
        'amount' => 'integer',
        'total_taxes' => 'integer',
        'total_fees' => 'integer',
        'total_deductions' => 'integer',
        'expires_at' => 'datetime',
        'user_id' => 'integer',
        'location_id' => 'integer',
        'creator_id' => 'integer',
        'updater_id' => 'integer',
    ];

    public static function boot()
    {
        parent::boot();

        static::saving(function ($cart) {
            $cart->generatePricing();

            if (empty($cart->total_deductions)) {
                $cart->total_deductions = 0;
            }
        });

        static::deleting(function ($cart) {
            $cart->releaseItemsHolds();
        });
    }

    public function markVouchersAsUsed(): self
    {
        if (app()->has(VoucherService::class)) {
            app(VoucherService::class)->markVouchersAsUsed($this, $this->order_id);
        }

        return $this;
    }

    public function issuePartialRedemptionVoucher(): self
    {
        if ($this->total_deductions < $this->amount + $this->total_taxes + $this->total_fees) {
            return $this;
        }

        if (app()->has(VoucherService::class)) {
            app(VoucherService::class)->issueCartPartialRedemptionVoucher($this);
        }

        return $this;
    }

    public function processOrder(PaymentInterface $payment): Order
    {
        if (! $this->canConvert()) {
            throw new \Exception('Cart not valid.');
        }

        $order = Order::create([
            'customer_id' => $payment->getCustomer()->getId(),
            'location_id' => $this->location_id,
            'amount' => $this->amount,
            'total_taxes' => $this->total_taxes,
            'total_fees' => $this->total_fees,
        ]);

        $this->order_id = $order->id;
        $this->save();

        foreach ($this->cartItems()->get() as $cartItem) {
            $cartItem->createBooking();
        }

        $payment->setOrder($order);

        $this->markVouchersAsUsed()
            ->issuePartialRedemptionVoucher();
        $order->refresh();

        /**
         * TODO - ensure handled in Voucher package as event listener
        if ($order->hasPartialRedemptionVoucher()) {
            $order
                ->partialRedemptionVoucher
                ->customer
                ->user
                ->notify(new PartialRedemptionVoucherCreated($order->partialRedemptionVoucher));
        }
         */

        $this->delete();

        event(new BookingOrderProcessed($order));
        // Event Listeners send confirmation email, update slot, block slot if private game. Will also need to send notification to staff.

        return $order;
    }

    public function applyVoucherCode(string $code): self
    {
        if (app()->has(VoucherService::class)) {
            app(VoucherService::class)->applyCodeToCart($this, $code);

            return $this->updateCartTotalDeductions();
        }

        return $this;
    }

    public function applyDiscountCode(string $code): self
    {
        if (app()->has(DiscountService::class)) {
            app(DiscountService::class)->applyCodeToCart($this, $code);

            return $this->updateCartTotalDeductions();
        }

        return $this;
    }

    public function updateCartTotalDeductions(): self
    {
        $this->total_deductions = $this->getCartItemTotalDeductions()
            ->plus($this->getVoucherDeductions())
            ->plus($this->getDiscountDeductions())
            ->getUnscaledAmount()
            ->toInt();

        $this->save();

        return $this;
    }

    public function getCartItemTotalDeductions(): Money
    {
        return $this->cartItems->reduce(function (Money $totalDeductions, CartItem $cartItem) {
            return $totalDeductions->plus(Money::ofMinor($cartItem->total_deductions ?? 0, 'USD'));
        }, Money::ofMinor(0, 'USD'));
    }

    public function getVoucherDeductions(): Money
    {
        if (app()->has(VoucherService::class)) {
            return app(VoucherService::class)->calculateVoucherDeductions($this);
        }

        return Money::ofMinor(0, 'USD');
    }

    public function getDiscountDeductions(): Money
    {
        if (app()->has(DiscountService::class)) {
            return app(DiscountService::class)->calculateDiscountDeductions($this);
        }

        return Money::ofMinor(0, 'USD');
    }

    public function getDeductionCodesAttribute()
    {
        return $this->vouchers->concat($this->discounts);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function canConvert(): bool
    {
        if (empty($this->amount)) {
            return false;
        }

        if (! $this->cartItems()->exists()) {
            return false;
        }

        if ($this->hasExpired()) {
            return false;
        }

        return true;
    }

    public function hasExpired(): bool
    {
        $now = Carbon::now();

        return $this->expires_at->lt($now);
    }

    public function hasInRoomMonitors(): bool
    {
        return $this->cartItems->contains(function (CartItem $item) {
            return $item->getRoom()->hasInRoomMonitors();
        });
    }

    public function getTotalAmountAttribute(): int
    {
        if ($this->total_deductions > $this->amount + $this->total_taxes + $this->total_fees) {
            return 0;
        }

        return $this->amount + $this->total_taxes + $this->total_fees - $this->total_deductions;
    }

    public function generatePricing(): self
    {
        $amount = 0;
        $fees = 0;
        $taxes = 0;

        if (! empty($this->cartItems()->exists())) {
            foreach ($this->cartItems()->get() as $item) {
                $amount += $item->amount;
                $fees += $item->total_fees;
                $taxes += $item->total_taxes;
            }
        }

        $this->amount = ($amount > 0) ? $amount : 0;
        $this->total_fees = $fees;
        $this->total_taxes = $taxes;

        return $this;
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function hasSlot(string $slotNumber): bool
    {
        return $this->cartItems()
            ->where('slot_number', $slotNumber)
            ->exists();
    }

    public function addItem(CartItem $cartItem): self
    {
        if (! empty($this->cartItems)) {
            $this->location_id = $cartItem->getRoom()->getLocation()->getId();
        } else {
            if ($this->location_id = $cartItem->getRoom()->getLocation()->getId()) {
                throw new \Exception('Cust must contain items from single location.');
            }
        }
        $cartItem->cart()->associate($this);
        $cartItem->save();
        $this->save();

        return $this;
    }

    public function releaseItemsHolds(): self
    {
        $this->cartItems->each(function ($item) {
            $item->releaseHold();
        });

        return $this;
    }

    public function getExpiresIn(): int
    {
        return Carbon::now()->diffInSeconds($this->expires_at);
    }

    public function updateItemsHolds(): self
    {
        // TODO - eliminate dependency on external package config
        $this->expires_at = now()->addSeconds(config('services.slot.hold.lifetime', 600));

        $this->cartItems->each(function (CartItem $item) {
            $item->setHold($this->user_id);
        });

        return $this;
    }

    public function addSlot(string $slotNumber, int $participants, bool $isPrivate): CartItem
    {
        $item = CartItem::makeFromSlot($slotNumber, $participants, $isPrivate);
        $this->updateItemsHolds();

        $this->addItem($item);

        return $item;
    }

    public function removeSlot(string $slotNumber): bool
    {
        return $this->cartItems()
            ->where('slot_number', $slotNumber)
            ->first()
            ->delete();
    }

    public function isEmpty(): bool
    {
        return ! $this->cartItems()->exists();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereDate('expires_at', '>', now())
            ->orWhere(function ($query) {
                $query->whereDate('expires_at', now())
                    ->whereTime('expires_at', '>', now());
            });
    }

    public function getTotalParticipants(): int
    {
        return (int) $this->cartItems()->sum('participants');
    }

    public function getLocation(): ?LocationInterface
    {
        return app(LocationService::class)->getLocation($this->location_id);
    }

    public function getUser(): ?UserInterface
    {
        return app(UserService::class)->getUser($this->user_id);
    }
}
