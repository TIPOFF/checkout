<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Transformers;

use League\Fractal\TransformerAbstract;
use Tipoff\Checkout\Models\Cart;

class CartTransformer extends TransformerAbstract
{
    protected $defaultIncludes = [
    ];

    protected $availableIncludes = [
        'cartItems',
    ];

    public function transform(Cart $cart)
    {
        return [
            'id' => $cart->id,
            'shipping' => $cart->getShipping()->getDiscountedAmount(),
            'item_amount' => $cart->getItemAmountTotal()->getDiscountedAmount(),
            'discounts' => $cart->getDiscounts(),
            'credits' => $cart->getCredits(),
            'tax' => $cart->getTax(),
            'user_id' => $cart->user_id,
            'expires_at' => $cart->getExpiresAt(),
            'location_id' => $cart->getLocationId(),
        ];
    }

    public function includeCartItems(Cart $cart)
    {
        return $this->collection($cart->cartItems, new CartItemTransformer);
    }
}
