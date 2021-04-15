<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Tipoff\Authorization\Models\EmailAddress;
use Tipoff\Checkout\Http\Requests\Cart\AddCodeRequest;
use Tipoff\Checkout\Http\Requests\Cart\CreateRequest;
use Tipoff\Checkout\Http\Requests\Cart\DeleteItemRequest;
use Tipoff\Checkout\Http\Requests\Cart\ShowRequest;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Support\Http\Controllers\BaseController;

class CartController extends BaseController
{
    public function show(ShowRequest $request)
    {
        $cart = Cart::activeCart($request->getEmailAddressId());

        return view('checkout::cart', ['cart' => $cart]);
    }

    public function addCode(AddCodeRequest $request)
    {
        Cart::activeCart($request->getEmailAddressId())->applyCode($request->code);

        return redirect(route('checkout.cart-show'));
    }

    public function deleteItem(DeleteItemRequest $request)
    {
        $cartItem = CartItem::query()->findOrFail($request->id);
        $this->authorize('delete', $cartItem);

        $cartItem->delete();

        return redirect(route('checkout.cart-show'));
    }
}
