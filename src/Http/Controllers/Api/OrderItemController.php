<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Tipoff\Checkout\Http\Requests\OrderItem\IndexRequest;
use Tipoff\Checkout\Http\Requests\OrderItem\ShowRequest;
use Tipoff\Checkout\Models\OrderItem;
use Tipoff\Checkout\Transformers\OrderItemTransformer;
use Tipoff\Support\Http\Controllers\Api\BaseApiController;

class OrderItemController extends BaseApiController
{
    protected OrderItemTransformer $transformer;

    public function __construct(OrderItemTransformer $transformer)
    {
        $this->transformer = $transformer;

        $this->authorizeResource(OrderItem::class);
    }

    public function index(IndexRequest $request): JsonResponse
    {
        $orderItems = OrderItem::query()->visibleBy($request->user())->paginate(
            $request->getPageSize()
        );

        return fractal($orderItems, $this->transformer)
            ->respond();
    }

    public function show(ShowRequest $request, OrderItem $orderItem): JsonResponse
    {
        return fractal($orderItem, $this->transformer)
            ->respond();
    }
}
