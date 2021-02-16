<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Tipoff\Support\Contracts\Checkout\CartDeduction;
use Tipoff\Support\Contracts\Checkout\CartInterface;
use Tipoff\Support\Contracts\Discounts\DiscountInterface;
use Tipoff\Support\Contracts\Payments\PaymentInterface;
use Tipoff\Support\Contracts\Vouchers\VoucherInterface;
use Tipoff\Checkout\Events\BookingOrderProcessed;
use Tipoff\Checkout\Exceptions\CartNotValidException;
use Tipoff\Checkout\Exceptions\InvalidDeductionCodeException;
use Tipoff\Checkout\Exceptions\MultipleLocationException;
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
 */
class Cart extends BaseModel implements CartInterface
{
    use HasPackageFactory;
    use SoftDeletes;

    protected $casts = [
        'id' => 'integer',
        'amount' => 'integer',
        'total_taxes' => 'integer',
        'total_fees' => 'integer',
        'total_deductions' => 'integer',
        'expires_at' => 'datetime',
        'user_id' => 'integer',
        'location_id' => 'integer',
    ];

    private static array $deductionTypes = [
        VoucherInterface::class,
        DiscountInterface::class,
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
        $orderId = $this->order_id;

        $this->vouchers()->each(function ($voucher) use ($orderId) {
            $voucher->redeem();
            $voucher->order_id = $orderId;
            $voucher->save();
        });

        return $this;
    }

    public function processOrder(PaymentInterface $payment): Order
    {
        if (! $this->canConvert()) {
            throw new CartNotValidException();
        }

        $customer = $payment->getCustomer();

        /** @var Order $order */
        $order = Order::create([
            'customer_id' => $customer ? $customer->getId() : null,
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

        static::activeDeductions()
            ->each(function (CartDeduction $deduction) {
                $deduction::markCartDeductionsAsUsed($this);
            });

        $this->issuePartialRedemptionVoucher();
        $order->refresh();

        $this->delete();

        event(new BookingOrderProcessed($order));
        // Event Listeners send confirmation email, update slot, block slot if private game. Will also need to send notification to staff.

        return $order;
    }

    private function issuePartialRedemptionVoucher(): self
    {
        if ($this->total_deductions < $this->amount + $this->total_taxes + $this->total_fees) {
            return $this;
        }

        if (app()->has(VoucherInterface::class)) {
            /** @var VoucherInterface $voucherInterface */
            $voucherInterface = app(VoucherInterface::class);

            $amount = $this->total_deductions - ($this->amount + $this->total_taxes + $this->total_fees);
            $voucher = $voucherInterface::issuePartialRedemptionVoucher($this, $this->location_id,  $amount, $this->user_id);

            $order = $this->order;
            $order->partial_redemption_voucher_id = $voucher->getId();
            $order->save();
        }

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
        return static::activeDeductions()
            ->reduce(function (array $codes, CartDeduction $deduction) {
                return array_merge($codes, $deduction->getCodesForCart($this));
            }, []);
    }

    public function location()
    {
        return $this->belongsTo(app('location'));
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
            return ($item->room->theme_id == 1 ||
                $item->room->theme_id == 2);
        });
    }

    public function scopeFilter(Builder $query, array $filters): Builder
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
            $this->location_id = $cartItem->room->location_id;
        } else {
            if ($this->location_id = $cartItem->room->location_id) {
                throw new MultipleLocationException();
            }
        }
        $cartItem->cart()->associate($this);
        $cartItem->save();
        $this->save();

        return $this;
    }

    public function releaseItemsHolds(): self
    {
        $this->cartItems->each(function (CartItem $item) {
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

    public function scopeVisibleBy(Builder $query, $user): Builder
    {
        return $query;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereDate('expires_at', '>', now())
            ->orWhere(function ($query) {
                $query->whereDate('expires_at', now())
                    ->whereTime('expires_at', '>', now());
            });
    }

    /******************************
     * CartInterface Implementation
     ******************************/

    private static function activeDeductions(): Collection
    {
        return collect(static::$deductionTypes)
            ->filter(function (string $type) {
                return app()->has($type);
            })
            ->map(function (string $type) {
                return app($type);
            });
    }

    private function findDeductionByCode(string $code): ?CartDeduction
    {
        return static::activeDeductions()
                ->first(function (CartDeduction $deduction) use ($code) {
                    return $deduction::findDeductionByCode($code);
                });
    }

    public function updateTotalDeductions(): self
    {
        $totalDeductions = $this->cartItems->reduce(function (int $totalDeductions, CartItem $cartItem) {
            return $totalDeductions + $cartItem->total_deductions;
        }, 0);

        $totalDeductions = static::activeDeductions()
            ->reduce(function (int $totalDeductions, CartDeduction $deduction) {
                return $totalDeductions + $deduction::calculateCartDeduction($this)->getUnscaledAmount()->toInt();
            }, $totalDeductions);

        $this->total_deductions = $totalDeductions;
        $this->save();

        return $this;
    }

    public function applyDeductionCode(string $code): CartInterface
    {
        $deduction = $this->findDeductionByCode($code);

        if (empty($deduction)) {
            throw new InvalidDeductionCodeException($code);
        }

        $deduction->applyToCart($this);

        return $this->updateTotalDeductions();
    }

    public function getTotalParticipants(): int
    {
        return (int) $this->cartItems()->sum('participants');
    }

    public function getCartItems(): array
    {
        return $this->cartItems->toArray();
    }

    public static function activeCart(int $userId): CartInterface
    {
        $cart = Cart::query()
            ->where('user_id', $userId)
            ->active()
            ->orderByDesc('id')
            ->first();

        return $cart ?: static::create([
            'user_id' => $userId,
        ]);
    }
}
