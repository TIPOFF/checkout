<?php

declare(strict_types=1);

namespace Tipoff\Checkout\View\Components;

use Illuminate\View\View;

class CartDeductionComponent extends BaseDeductionComponent
{
    public function render()
    {
        /** @var View $view */
        $view = view('components.cart-deduction');

        return $view;
    }
}
