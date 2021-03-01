<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Transformers;

use League\Fractal\TransformerAbstract;
use Tipoff\Checkout\Models\Order;

class OrderTransformer extends TransformerAbstract
{
    protected $defaultIncludes = [
    ];

    protected $availableIncludes = [
        'orderItems',
    ];

    public function transform(Order $order)
    {
        return [
            'id' => $order->id,
            'shipping' => $order->getShipping()->getDiscountedAmount(),
            'item_amount' => $order->getItemAmount()->getDiscountedAmount(),
            'discounts' => $order->getDiscounts(),
            'credits' => $order->getCredits(),
            'tax' => $order->getTax(),
            'user_id' => $order->user_id,
            'location_id' => $order->getLocationId(),
        ];
    }

    public function includeOrderItems(Order $order)
    {
        return $this->collection($order->orderItems, new OrderItemTransformer);
    }
}
