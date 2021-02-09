<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tipoff\Checkout\Events\BookingOrderProcessed;
use Tipoff\Checkout\Notifications\PartialRedemptionVoucherCreated;
use Tipoff\Checkout\Services\CheckoutService;
use Tipoff\Support\Traits\HasPackageFactory;

class Cart extends Model
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
        'expires_at' => 'datetime',];

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

    /**
     * Mark coucher as used.
     *
     * @return self
     */
    public function markVouchersAsUsed()
    {
        $orderId = $this->order_id;

        $this->vouchers()->each(function ($voucher) use ($orderId) {
            $voucher->redeem();
            $voucher->order_id = $orderId;
            $voucher->save();
        });

        return $this;
    }

    /**
     * Issu vouchers in case of partial redemption.
     *
     * @return self
     */
    public function issuePartialRedemptionVoucher()
    {
        if ($this->total_deductions < $this->amount + $this->total_taxes + $this->total_fees) {
            return $this;
        }

        app(CheckoutService::class)->issueCartPartialRedemptionVoucher($this);

        return $this;
    }

    /**
     * Change cart to order.
     *
     * @return Order
     */
    public function processOrder(Model $payment)
    {
        if (! $this->canConvert()) {
            throw new \Exception('Cart not valid.');
        }

        $order = Order::create([
            'customer_id' => $payment->customer_id,
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

        $payment->order_id = $order->id;
        $payment->save();

        $this->markVouchersAsUsed();
        $this->issuePartialRedemptionVoucher();
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

    /**
     * Apply deduction to cart.
     *
     * @param Model $deduction
     * @return self
     */
    public function applyDeduction($deduction)
    {
        app(CheckoutService::class)->applyDeductionToCart($deduction, $this);

        return $this;
    }

    /**
     * Apply deduction code to cart.
     *
     * @param string $code
     * @return self
     */
    public function applyCode($code)
    {
        app(CheckoutService::class)->applyCodeToCart($code, $this);

        return $this;
    }

    /**
     * Apply discount to cart.
     *
     * @param Model $discount
     * @return self
     */
    public function applyDiscount($discount)
    {
        app(CheckoutService::class)->applyDiscountToCart($discount, $this);

        return $this;
    }

    /**
     * Apply voucher to cart.
     *
     * @param Model $voucher
     * @return self
     */
    public function applyVoucher($voucher)
    {
        app(CheckoutService::class)->applyVoucherToCart($voucher, $this);

        return $this;
    }

    public function vouchers()
    {
        return $this->belongsToMany(app('voucher'))->withTimestamps();
    }

    public function discounts()
    {
        return $this->belongsToMany(app('discount'))->withTimestamps();
    }

    public function getDeductionCodesAttribute()
    {
        return $this->vouchers->concat($this->discounts);
    }

    public function location()
    {
        return $this->belongsTo(app('location'));
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Check if cart can convert to order.
     *
     * @return bool
     */
    public function canConvert()
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

    public function hasExpired()
    {
        $now = Carbon::now();

        return $this->expires_at->lt($now);
    }

    public function hasInRoomMonitors()
    {
        return $this->cartItems->contains(function (CartItem $item) {
            return ($item->room->theme_id == 1 ||
                $item->room->theme_id == 2);
        });
    }

    /**
     * Scope a query to apply filters.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param $filters array
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilter($query, $filters)
    {
        if (empty($filters)) {
            return $query;
        }

        /*
         * TODO - implement or remove function
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

    /**
     * Get total amount.
     *
     * @return int
     */
    public function getTotalAmountAttribute(): int
    {
        if ($this->total_deductions > $this->amount + $this->total_taxes + $this->total_fees) {
            return 0;
        }

        return $this->amount + $this->total_taxes + $this->total_fees - $this->total_deductions;
    }

    /**
     * Generate amount, total_taxes and total_fees.
     *
     * @return self
     */
    public function generatePricing()
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

    /**
     * Add item to cart.
     *
     * @param CartItem $cartItem
     * @return self
     */
    public function addItem(CartItem $cartItem)
    {
        if (! empty($this->cartItems)) {
            $this->location_id = $cartItem->room->location_id;
        } else {
            if ($this->location_id = $cartItem->room->location_id) {
                throw new \Exception('Cust must contain items from single location.');
            }
        }
        $cartItem->cart()->associate($this);
        $cartItem->save();
        $this->save();

        return $this;
    }

    /**
     * Release hold on all cart items.
     *
     * @return self
     */
    public function releaseItemsHolds()
    {
        $this->cartItems->each(function ($item) {
            $item->releaseHold();
        });

        return $this;
    }

    /**
     * Get seconds to lock expiration.
     *
     * @return int
     */
    public function getExpiresIn()
    {
        return Carbon::now()->diffInSeconds($this->expires_at);
    }

    /**
     * Update hold on all cart items.
     *
     * @return self
     */
    public function updateItemsHolds()
    {
        $this->expires_at = now()->addSeconds(config('services.slot.hold.lifetime', 600));

        $this->cartItems->each(function ($item) {
            $item->setHold($this->user_id);
        });

        return $this;
    }

    /**
     * Add slot.
     *
     * @param string $slotNumber
     * @param int $participants
     * @param bool $isPrivate
     *
     * @return CartItem
     */
    public function addSlot($slotNumber, $participants, $isPrivate)
    {
        $item = CartItem::makeFromSlot($slotNumber, $participants, $isPrivate);
        $this->updateItemsHolds();

        $this->addItem($item);

        return $item;
    }

    public function removeSlot($slotNumber): bool
    {
        return $this->cartItems()
            ->where('slot_number', $slotNumber)
            ->first()
            ->delete();
    }

    /**
     * Check if cart is empty.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return ! $this->cartItems()->exists();
    }

    /**
     * Scope a query to rows visible by user.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param $user array
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVisibleBy($query, $user)
    {
        return $query;
    }

    public function scopeActive($query)
    {
        return $query->whereDate('expires_at', '>', now())
            ->orWhere(function ($query) {
                $query->whereDate('expires_at', now())
                    ->whereTime('expires_at', '>', now());
            });
    }

    /**
     * Get cart total participants.
     *
     * @return int
     */
    public function getTotalParticipants()
    {
        return (int) $this->cartItems()->sum('participants');
    }
}
