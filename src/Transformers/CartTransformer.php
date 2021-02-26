<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Transformers;

use League\Fractal\TransformerAbstract;
use Tipoff\Checkout\Models\Cart;

class CartTransformer extends TransformerAbstract
{
    /**
     * List of resources to automatically include.
     *
     * @var array
     */
    protected $defaultIncludes = [
        //
    ];

    /**
     * List of resources possible to include.
     *
     * @var array
     */
    protected $availableIncludes = [
        'cartItems',
    ];

    /**
     * A Fractal transformer.
     *
     * @return array
     */
    public function transform(Cart $cart)
    {
        return [
            'amount' => $cart->amount,
            'total_taxes' => $cart->total_taxes,
            'total_fees' => $cart->total_fees,
            'total_deductions' => $cart->total_deductions,
            'total_amount' => $cart->total_amount,
            'user_id' => $cart->user_id,
            'expires_at' => $cart->expires_at,
        ];
    }

    /**
     * Include cart items.
     */
    public function includeCartItems(Cart $cart)
    {
        return $this->collection($cart->cartItems, new CartItemTransformer);
    }
}
