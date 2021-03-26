<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Models;

use Assert\Assert;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Tipoff\Authorization\Models\User;
use Tipoff\Checkout\Enums\OrderStatus;
use Tipoff\Checkout\Filters\ItemFilter;
use Tipoff\Checkout\Models\Traits\IsItemContainer;
use Tipoff\Checkout\Services\Cart\ActiveAdjustments;
use Tipoff\Statuses\Traits\HasStatuses;
use Tipoff\Support\Contracts\Checkout\CodedCartAdjustment;
use Tipoff\Support\Contracts\Checkout\OrderInterface;
use Tipoff\Support\Contracts\Checkout\OrderItemInterface;
use Tipoff\Support\Contracts\Models\UserInterface;
use Tipoff\Support\Contracts\Sellable\Sellable;
use Tipoff\Support\Models\BaseModel;
use Tipoff\Support\Traits\HasPackageFactory;

/**
 * @property string order_number
 * // Relations
 * @property User user
 * @property Cart cart
 * @property Collection orderItems
 * @property Collection invoices
 * @property Collection payments
 * @property Collection vouchers
 * @property Collection discounts
 * @property Collection notes
 * @property int user_id
 */
class Order extends BaseModel implements OrderInterface
{
    use HasPackageFactory;
    use IsItemContainer;
    use HasStatuses;

    protected $guarded = [
        'id',
        'order_number',
        'item_amount_total',
        'item_amount_total_discounts',
        'tax',
    ];

    protected $casts = [
        'id' => 'integer',
        'shipping' => \Tipoff\Support\Casts\DiscountableValue::class,
        'item_amount_total' => \Tipoff\Support\Casts\DiscountableValue::class,
        'discounts' => 'integer',
        'credits' => 'integer',
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

        $order->user()->associate($cart->emailAddress->user);
        $order->shipping = $cart->getShipping();
        $order->item_amount_total = $cart->getItemAmountTotal();
        $order->discounts = $cart->getDiscounts();
        $order->credits = $cart->getCredits();
        $order->tax = $cart->getTax();
        $order->location_id = $cart->getLocationId();
        $order->save();

        $order->setOrderStatus(OrderStatus::PROCESSING());

        $cart->copyAddressesToTarget($order);

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

    public function user()
    {
        return $this->belongsTo(app('user'));
    }

    public function orderItems()
    {
        return $this->hasMany(app('order_item'));
    }

    public function cart()
    {
        return $this->hasOne(app('cart'));
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

    public function scopeByOrderStatus(Builder $query, OrderStatus $orderStatus): Builder
    {
        return $this->scopeByStatus($query, $orderStatus->toStatus());
    }

    //endregion

    //region PERMISSIONS

    public function scopeVisibleBy(Builder $query, UserInterface $user): Builder
    {
        return $query->where('user_id', '=', $user->getId());
    }

    public function isOwner(UserInterface $user): bool
    {
        return $this->user_id === $user->getId();
    }

    //endregion

    //region INTERFACE

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public static function itemFilter(): ItemFilter
    {
        return new ItemFilter(OrderItem::query());
    }

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

    public function setOrderStatus(OrderStatus $orderStatus): self
    {
        $this->setStatus((string) $orderStatus->getValue(), OrderStatus::statusType());

        return $this;
    }

    public function getOrderStatus(): ?OrderStatus
    {
        $status = $this->getStatus(OrderStatus::statusType());

        return $status ? OrderStatus::byValue((string) $status) : null;
    }

    public function getOrderStatusHistory(): Collection
    {
        return $this->getStatusHistory(OrderStatus::statusType());
    }

    public function getItems(): Collection
    {
        return $this->orderItems;
    }

    public function getCodes(): array
    {
        return (new ActiveAdjustments())()
            ->reduce(function (array $codes, CodedCartAdjustment $deduction) {
                return array_merge($codes, $deduction::getCodesForOrder($this));
            }, []);
    }

    //endregion
}
