<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Tipoff\Checkout\Http\Requests\Cart\DestroyCart;
use Tipoff\Checkout\Http\Requests\Cart\ShowCart;
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

    public function show(ShowCart $request): JsonResponse
    {
        $cart = Cart::activeCart($request->user()->id);

        return fractal($cart, $this->transformer)
            ->respond();
    }

    public function destroy(DestroyCart $request): Response
    {
        $request->user()->cart()->delete();

        return $this->respondSuccess();
    }
}
