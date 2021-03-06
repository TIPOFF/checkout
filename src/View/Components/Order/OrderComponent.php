<?php

declare(strict_types=1);

namespace Tipoff\Checkout\View\Components\Order;

use Illuminate\View\Component;
use Illuminate\View\View;
use Tipoff\Checkout\Models\Order;
use Tipoff\Checkout\Models\OrderItem;

class OrderComponent extends Component
{
    public Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function getItemComponent(OrderItem $item): string
    {
        return $item->getSellable()->getViewComponent('order-item') ?? 'tipoff-order-item';
    }

    public function render()
    {
        /** @var View $view */
        $view = view('checkout::components.order.order');

        return $view;
    }
}
