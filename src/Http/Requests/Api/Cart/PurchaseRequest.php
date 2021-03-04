<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Http\Requests\Api\Cart;

use Tipoff\Checkout\Exceptions\CartNotValidException;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Services\Cart\VerifyPurchasable;

class PurchaseRequest extends CartRequest
{
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            /** @var Cart|null $cart */
            $cart = auth()->id() ? Cart::activeCart(auth()->id()) : null;
            $this->validateCartIsPurchasable($cart, $validator);
        });
    }

    private function validateCartIsPurchasable(?Cart $cart, $validator)
    {
        if ($cart) {
            try {
                app(VerifyPurchasable::class)($cart);
            } catch (CartNotValidException $ex) {
                $validator->errors()->add('cart', $ex->getMessage());
            }
        } else {
            $validator->errors()->add('cart', 'Cart not available for purchase.');
        }
    }
}
