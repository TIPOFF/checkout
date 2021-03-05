<?php

declare(strict_types=1);

namespace Tipoff\Checkout\View\Components\Order;

use Illuminate\View\Component;
use Illuminate\View\View;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\Order;

class OrderComponent extends Component
{
    public Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function render()
    {
        /** @var View $view */
        $view = view('checkout::components.order.order');

        return $view;
    }
}
