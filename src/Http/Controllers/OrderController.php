<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Http\Controllers;

use Tipoff\Checkout\Http\Requests\Cart\ShowRequest;
use Tipoff\Checkout\Http\Requests\Order\IndexRequest;
use Tipoff\Checkout\Models\Order;
use Tipoff\Support\Http\Controllers\BaseController;

class OrderController extends BaseController
{
    public function index(IndexRequest $request)
    {
        $orders = Order::query()->visibleBy($request->user())->get();

        return view('checkout::orders', ['orders' => $orders]);
    }

    public function show(ShowRequest $request, Order $order)
    {
        $this->authorize('view', $order);

        return view('checkout::order', ['order' => $order]);
    }
}
