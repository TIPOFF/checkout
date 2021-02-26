<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Tipoff\Checkout\Http\Requests\CartItem\DestroyCartItem;
use Tipoff\Checkout\Http\Requests\CartItem\IndexCartItems;
use Tipoff\Checkout\Http\Requests\CartItem\ShowCartItem;
use Tipoff\Checkout\Http\Requests\CartItem\StoreCartItem;
use Tipoff\Checkout\Http\Requests\CartItem\UpdateCartItem;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Checkout\Transformers\CartItemTransformer;
use Tipoff\Support\Http\Controllers\Api\BaseApiController;

class CartItemController extends BaseApiController
{
    protected CartItemTransformer $transformer;

    public function __construct(CartItemTransformer $transformer)
    {
        $this->transformer = $transformer;

        $this->authorizeResource(CartItem::class, 'cartItem');
    }

    public function index(IndexCartItems $request): JsonResponse
    {
        $cartItems = CartItem::paginate(
            $request->getPageSize()
        );

        return fractal($cartItems, $this->transformer)
            ->respond();
    }

    public function store(StoreCartItem $request): JsonResponse
    {
        $cartItem = CartItem::make($request->all());
        $cartItem->save();

        return fractal($cartItem, $this->transformer)
            ->respond();
    }

    public function show(ShowCartItem $request, CartItem $cartItem): JsonResponse
    {
        return fractal($cartItem, $this->transformer)
            ->respond();
    }

    public function update(UpdateCartItem $request, CartItem $cartItem): JsonResponse
    {
        $cartItem->fill($request->all())
            ->save();

        return fractal($cartItem, $this->transformer)
                ->parseIncludes($request->include)
                ->respond();
    }

    public function destroy(DestroyCartItem $request, CartItem $cartItem): Response
    {
        if ($cartItem->delete()) {
            return $this->respondSuccess();
        }

        return $this->respondWithError('Failed to delete.');
    }
}
