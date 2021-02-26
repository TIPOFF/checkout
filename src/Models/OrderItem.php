<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Models;

use Tipoff\Checkout\Models\Traits\IsItem;
use Tipoff\Support\Contracts\Checkout\OrderInterface;
use Tipoff\Support\Contracts\Checkout\OrderItemInterface;
use Tipoff\Support\Models\BaseModel;
use Tipoff\Support\Traits\HasPackageFactory;

class OrderItem extends BaseModel implements OrderItemInterface
{
    use HasPackageFactory;
    use IsItem;

    protected $touches = [
        'order',
    ];

    protected $casts = [
        'id' => 'integer',
        'amount' => \Tipoff\Support\Casts\DiscountableValue::class,
        'quantity' => 'integer',
        'tax' => 'integer',
        'meta_data' => 'json',
        // Raw relation id access
        'sellable_id' => 'integer',
        'order_id' => 'integer',
        'parent_id' => 'integer',
        'location_id' => 'integer',
        'creator_id' => 'integer',
        'updater_id' => 'integer',
    ];

    public static function createFromCartItem(Order $order, CartItem $cartItem, OrderItem $parentItem = null): self
    {
        // Build by field to avoid fillable permissions
        $orderItem = new static;

        // Relations
        $orderItem->order()->associate($order);
        $orderItem->sellable()->associate($cartItem->sellable);
        $orderItem->parent()->associate($parentItem);

        // Fields
        $orderItem->item_id = $cartItem->getItemId();
        $orderItem->description = $cartItem->getDescription();
        $orderItem->amount = $cartItem->getAmount();
        $orderItem->quantity = $cartItem->getQuantity();
        $orderItem->tax = $cartItem->getTax();
        $orderItem->tax_code = $cartItem->getTaxCode();
        $orderItem->location_id = $cartItem->getLocationId();
        $orderItem->meta_data = $cartItem->getMetaData(null);
        $orderItem->save();

        $order->load('orderItems');
        $order->save();

        return $orderItem;
    }

    //region RELATIONSHIPS

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    //endregion

    //region INTERFACE IMPLEMENTATION

    public function getOrder(): ?OrderInterface
    {
        return $this->order;
    }

    //endregion
}