<?php

declare(strict_types=1);

namespace Tipoff\Checkout\View\Components\Order;

use Illuminate\View\View;
use Tipoff\Checkout\View\Components\BaseDeductionComponent;

class OrderDeductionComponent extends BaseDeductionComponent
{
    public function render()
    {
        /** @var View $view */
        $view = view('checkout::components.order.order-deduction');

        return $view;
    }
}
