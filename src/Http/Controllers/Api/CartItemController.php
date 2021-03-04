<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Tipoff\Checkout\Http\Requests\Api\CartItem\DestroyRequest;
use Tipoff\Checkout\Http\Requests\Api\CartItem\IndexRequest;
use Tipoff\Checkout\Http\Requests\Api\CartItem\ShowRequest;
use Tipoff\Checkout\Http\Requests\Api\CartItem\StoreRequest;
use Tipoff\Checkout\Http\Requests\Api\CartItem\UpdateRequest;
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

        $this->authorizeResource(CartItem::class);
    }

    public function index(IndexRequest $request): JsonResponse
    {
        $cartItems = CartItem::query()->visibleBy($request->user())->paginate(
            $request->getPageSize()
        );

        return fractal($cartItems, $this->transformer)
            ->respond();
    }

    public function store(StoreRequest $request): JsonResponse
    {
        $sellableType = $request->sellable_type;


        $sellable = $sellableType::query()->findOrFail($request->sellable_id);

        $cartItem = Cart::createItem(
            $sellable,
            $request->item_id,
            (int) $request->amount,
            (int) $request->quantity ?? 1
        );

        $cartItem
            ->setLocationId((int) $request->location_id)
            ->setTaxCode($request->tax_code);

        if ($request->has('expires_at')) {
            $cartItem->setExpiresAt(Carbon::parse($request->expires_at));
        }

        $cartItem = Cart::activeCart($request->user()->id)->upsertItem($cartItem);

        return fractal($cartItem, $this->transformer)
            ->respond();
    }

    public function show(ShowRequest $request, CartItem $cartItem): JsonResponse
    {
        return fractal($cartItem, $this->transformer)
            ->respond();
    }

    public function update(UpdateRequest $request, CartItem $cartItem): JsonResponse
    {
        $cartItem->setQuantity((int) $request->quantity);
        $cartItem->save();

        return fractal($cartItem, $this->transformer)
                ->respond();
    }

    public function destroy(DestroyRequest $request, CartItem $cartItem): JsonResponse
    {
        if ($cartItem->delete()) {
            return $this->respondSuccess();
        }

        return $this->respondWithError('Failed to delete.');
    }
}
