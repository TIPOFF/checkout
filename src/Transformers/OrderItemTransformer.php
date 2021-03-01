<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Transformers;

use League\Fractal\TransformerAbstract;
use Tipoff\Checkout\Models\OrderItem;

class OrderItemTransformer extends TransformerAbstract
{
    protected $defaultIncludes = [
    ];

    protected $availableIncludes = [
    ];

    public function transform(OrderItem $orderItem)
    {
        return [
            'id' => $orderItem->id,
            'item_id' => $orderItem->getItemId(),
            'description' => $orderItem->getDescription(),
            'quantity' => $orderItem->getQuantity(),
            'amount' => $orderItem->getAmount()->getOriginalAmount(),
            'amount_discounts' => $orderItem->getAmount()->getDiscounts(),
            'tax' => $orderItem->getTax(),
            'tax_code' => $orderItem->getTaxCode(),
            'location_id' => $orderItem->getLocationId(),
        ];
    }
}
