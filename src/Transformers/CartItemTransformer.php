<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Transformers;

use Tipoff\Checkout\Models\CartItem;

class CartItemTransformer extends BaseItemTransformer
{
    /**
     * @inheritDoc
     */
    public function transform($cartItem)
    {
        /** @var CartItem $cartItem */
        return array_merge(parent::transform($cartItem), [
            'expires_at' => $cartItem->getExpiresAt(),
        ]);
    }
}
