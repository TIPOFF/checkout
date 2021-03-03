<?php

declare(strict_types=1);

namespace Tipoff\Checkout\View\Components;


use Illuminate\View\Component;
use Illuminate\View\View;
use Tipoff\Checkout\Models\Cart;

class CartComponent extends Component
{
    public ?Cart $cart;

    public function __construct(?Cart $cart)
    {
        $this->cart = $cart ?: (auth()->id() ? Cart::activeCart(auth()->id()) : null);
    }

    public function render()
    {
        if ($this->cart) {
            /** @var View $view */
            $view = view($this->cart->isEmpty() ? 'components.cart-empty' : 'components.cart');

            return $view;
        }

        return '';
    }
}
