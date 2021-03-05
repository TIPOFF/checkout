<?php

declare(strict_types=1);

namespace Tipoff\Checkout\View\Components\Order;

use Illuminate\View\Component;
use Illuminate\View\View;
use Tipoff\Checkout\Models\OrderItem;
use Tipoff\Support\Contracts\Sellable\Sellable;

class OrderItemComponent extends Component
{
    public OrderItem $orderItem;
    public Sellable $sellable;

    public function __construct(OrderItem $orderItem, Sellable $sellable)
    {
        $this->orderItem = $orderItem;
        $this->sellable = $sellable;
    }

    public function render()
    {
        /** @var View $view */
        $view = view('checkout::components.order.order-item');

        return $view;
    }
}
