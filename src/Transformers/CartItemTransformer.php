<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Transformers;

use League\Fractal\TransformerAbstract;
use Tipoff\Checkout\Models\CartItem;

class CartItemTransformer extends TransformerAbstract
{
    protected $defaultIncludes = [
    ];

    protected $availableIncludes = [
    ];

    public function transform(CartItem $cartItem)
    {
        return [
            'id' => $cartItem->id,
            'item_id' => $cartItem->getItemId(),
            'description' => $cartItem->getDescription(),
            'quantity' => $cartItem->getQuantity(),
            'amount_each' => $cartItem->getAmountEach()->getOriginalAmount(),
            'amount_each_discounts' => $cartItem->getAmountEach()->getDiscounts(),
            'amount_total' => $cartItem->getAmountTotal()->getOriginalAmount(),
            'amount_total_discounts' => $cartItem->getAmountTotal()->getDiscounts(),
            'tax' => $cartItem->getTax(),
            'tax_code' => $cartItem->getTaxCode(),
            'expires_at' => $cartItem->getExpiresAt(),
            'location_id' => $cartItem->getLocationId(),
        ];
    }
}
