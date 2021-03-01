<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Tipoff\Checkout\Http\Requests\Order\IndexRequest;
use Tipoff\Checkout\Http\Requests\Order\ShowRequest;
use Tipoff\Checkout\Models\Order;
use Tipoff\Checkout\Transformers\OrderTransformer;
use Tipoff\Support\Http\Controllers\Api\BaseApiController;

class OrderController extends BaseApiController
{
    protected OrderTransformer $transformer;

    public function __construct(OrderTransformer $transformer)
    {
        $this->transformer = $transformer;

        $this->authorizeResource(Order::class);
    }

    public function index(IndexRequest $request): JsonResponse
    {
        $orders = Order::query()->visibleBy($request->user())->paginate(
            $request->getPageSize()
        );

        return fractal($orders, $this->transformer)
            ->respond();
    }

    public function show(ShowRequest $request, Order $order): JsonResponse
    {
        return fractal($order, $this->transformer)
            ->respond();
    }
}
