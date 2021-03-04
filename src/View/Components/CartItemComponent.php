<?php

declare(strict_types=1);

namespace Tipoff\Checkout\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Support\Contracts\Sellable\Sellable;

class CartItemComponent extends Component
{
    public CartItem $cartItem;
    public Sellable $sellable;

    public function __construct(CartItem $cartItem, Sellable $sellable)
    {
        $this->cartItem = $cartItem;
        $this->sellable = $sellable;
    }

    public function render()
    {
        /** @var View $view */
        $view = view('checkout::components.cart-item');

        return $view;
    }
}
