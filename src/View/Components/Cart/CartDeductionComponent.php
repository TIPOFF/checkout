<?php

declare(strict_types=1);

namespace Tipoff\Checkout\View\Components\Cart;

use Illuminate\View\View;
use Tipoff\Checkout\View\Components\BaseDeductionComponent;

class CartDeductionComponent extends BaseDeductionComponent
{
    public function render()
    {
        /** @var View $view */
        $view = view('checkout::components.cart.cart-deduction');

        return $view;
    }
}
