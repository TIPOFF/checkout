<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Models;

use Assert\Assert;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Tipoff\Checkout\Models\Traits\IsItemContainer;
use Tipoff\Support\Contracts\Checkout\OrderInterface;
use Tipoff\Support\Contracts\Checkout\OrderItemInterface;
use Tipoff\Support\Contracts\Sellable\Sellable;
use Tipoff\Support\Models\BaseModel;
use Tipoff\Support\Traits\HasPackageFactory;

/**
 * @property string order_number
 * // Relations
 * @property Collection orderItems
 * @property Collection invoices
 * @property Collection payments
 * @property Collection vouchers
 * @property Collection discounts
 * @property Collection notes
 */
class Order extends BaseModel implements OrderInterface
{
    use HasPackageFactory;
    use IsItemContainer;

    protected $guarded = [
        'id',
        'order_number',
    ];

    protected $casts = [
        'id' => 'integer',
        'shipping' => \Tipoff\Support\Casts\DiscountableValue::class,
        'item_amount' => \Tipoff\Support\Casts\DiscountableValue::class,
        'discounts' => 'integer',
        'tax' => 'integer',
        'user_id' => 'integer',
        'location_id' => 'integer',
        'creator_id' => 'integer',
        'updater_id' => 'integer',
    ];

    public static function createFromCart(Cart $cart): self
    {
        // Build by field to avoid fillable permissions
        $order = new static;

        $order->user()->associate($cart->user);
        $order->shipping = $cart->getShipping();
        $order->item_amount = $cart->getItemAmount();
        $order->discounts = $cart->getDiscounts();
        $order->tax = $cart->getTax();
        $order->location_id = $cart->getLocationId();
        $order->save();

        return $order;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Order $order) {
            $order->order_number = $order->order_number ?: $order->generateOrderNumber();
        });

        static::saving(function (Order $order) {
            Assert::lazy()
                ->that($order->user_id, 'user_id')->notEmpty('An order must belong to a user.')
                ->verifyNow();
        });
    }

    protected function generateOrderNumber(): string
    {
        do {
            $token = Str::of(Carbon::now('America/New_York')->format('ymdB'))->substr(1, 7) . Str::upper(Str::random(2));
        } while (static::query()->where('order_number', $token)->count()); //check if the token already exists and if it does, try again

        return $token;
    }

    //region RELATIONSHIPS

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function purchasedVouchers()
    {
        return $this->hasMany(app('voucher'), 'purchase_order_id');
    }

    public function invoices()
    {
        return $this->hasMany(app('invoice'));
    }

    public function payments()
    {
        return $this->hasMany(app('payment'));
    }

    public function vouchers()
    {
        return $this->hasMany(app('voucher'));
    }

    public function discounts()
    {
        return $this->belongsToMany(app('discount'));
    }

    public function notes()
    {
        return $this->morphMany(app('note'), 'noteable');
    }

    //endregion

    //region SCOPES

    //endregion

    //region INTERFACE

    public function findItem(Sellable $sellable, string $itemId): ?OrderItemInterface
    {
        /** @var OrderItem|null $result */
        $result = $this->orderItems()->bySellableId($sellable, $itemId)->first();

        return $result;
    }

    public function getOrderNumber(): string
    {
        return $this->order_number;
    }

    public function getItems(): Collection
    {
        return $this->orderItems;
    }

    //endregion
}
