<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Transformers;

use Tipoff\Checkout\Models\Cart;
use Tipoff\Support\Transformers\BaseTransformer;

class CartTransformer extends BaseItemContainerTransformer
{
    public function getItemTransformer(): BaseTransformer
    {
        return new CartItemTransformer();
    }

    /**
     * @inheritDoc
     */
    public function transform($cart)
    {
        /** @var Cart $cart */
        return array_merge(parent::transform($cart), [
            'expires_at' => $cart->getExpiresAt(),
        ]);
    }
}
