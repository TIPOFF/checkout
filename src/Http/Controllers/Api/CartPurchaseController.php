<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Tipoff\Checkout\Http\Requests\Cart\PurchaseRequest;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Transformers\OrderTransformer;
use Tipoff\Support\Http\Controllers\Api\BaseApiController;

class CartPurchaseController extends BaseApiController
{
    protected OrderTransformer $transformer;

    public function __construct(OrderTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    public function __invoke(PurchaseRequest $request): JsonResponse
    {
        $cart = Cart::activeCart($request->user()->id);

        $order = DB::transaction(function () use ($cart) {
            // Final check all is good to go
            $cart->verifyPurchasable();

            // Complete purchase
            return $cart->completePurchase();
        });

        return fractal($order, $this->transformer)
            ->respond();
    }
}
