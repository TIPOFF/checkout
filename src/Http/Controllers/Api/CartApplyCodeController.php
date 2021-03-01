<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Tipoff\Checkout\Http\Requests\Cart\ApplyCodeRequest;
use Tipoff\Checkout\Http\Requests\Cart\DestroyRequest;
use Tipoff\Checkout\Http\Requests\Cart\PurchaseRequest;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Transformers\CartTransformer;
use Tipoff\Checkout\Transformers\OrderTransformer;
use Tipoff\Support\Http\Controllers\Api\BaseApiController;

class CartApplyCodeController extends BaseApiController
{
    protected CartTransformer $transformer;

    public function __construct(CartTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    public function __invoke(ApplyCodeRequest $request): JsonResponse
    {
        $cart = Cart::activeCart($request->user()->id);

        $cart->applyCode($request->code);

        return fractal($cart, $this->transformer)
            ->respond();
    }
}
