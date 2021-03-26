<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Tipoff\Checkout\Http\Requests\Api\Cart\DestroyRequest;
use Tipoff\Checkout\Http\Requests\Api\Cart\ShowRequest;
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
        $cart = $request->getEmailAddressId() ? Cart::activeCart($request->getEmailAddressId()) : null;

        return fractal($cart, $this->transformer)
            ->respond();
    }

    public function destroy(DestroyRequest $request): JsonResponse
    {
        if ($emailAddressId = $request->getEmailAddressId()) {
            /** @var Cart $cart */
            $cart = Cart::activeCart($emailAddressId);
            $cart->delete();
        }

        return $this->respondSuccess();
    }
}
