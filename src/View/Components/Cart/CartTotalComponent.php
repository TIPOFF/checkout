<?php

declare(strict_types=1);

namespace Tipoff\Checkout\View\Components\Cart;

use Illuminate\View\Component;
use Illuminate\View\View;
use Tipoff\Checkout\Models\Cart;

class CartTotalComponent extends Component
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
            $view = view('checkout::components.cart.cart-total');

            return $view;
        }

        return '';
    }
}
