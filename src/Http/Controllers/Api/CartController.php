<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Tipoff\Checkout\Http\Requests\Cart\DestroyRequest;
use Tipoff\Checkout\Http\Requests\Cart\ShowRequest;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Transformers\CartTransformer;
use Tipoff\Support\Http\Controllers\Api\BaseApiController;

class CartController extends BaseApiController
{
    protected CartTransformer $transformer;

    public function __construct(CartTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    public function show(ShowRequest $request): JsonResponse
    {
        $cart = $request->user() ? Cart::activeCart($request->user()->id) : null;

        return fractal($cart, $this->transformer)
            ->respond();
    }

    public function destroy(DestroyRequest $request): JsonResponse
    {
        if ($request->user()) {
            /** @var Cart $cart */
            $cart = Cart::activeCart($request->user()->id);
            $cart->delete();
        }

        return $this->respondSuccess();
    }
}
