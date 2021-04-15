<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Tipoff\Checkout\Http\Requests\Checkout\ConfirmationRequest;
use Tipoff\Checkout\Http\Requests\Checkout\PurchaseRequest;
use Tipoff\Checkout\Http\Requests\Checkout\ShowRequest;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\Order;
use Tipoff\Support\Http\Controllers\BaseController;

class CheckoutController extends BaseController
{
    private const SESSION_CONFIRMATION_ID = 'tipoff.confirmation_id';

    public function show(ShowRequest $request)
    {
        $cart = Cart::activeCart($request->getEmailAddressId());

        return view('checkout::checkout', ['cart' => $cart]);
    }

    public function purchase(PurchaseRequest $request)
    {
        $cart = Cart::activeCart($request->getEmailAddressId());
        $order = DB::transaction(function () use ($cart) {
            $cart->verifyPurchasable();
            return $cart->completePurchase();
        });

        session([self::SESSION_CONFIRMATION_ID => $order->id]);

        return redirect(route('checkout.confirmation'));
    }

    public function confirmation(ConfirmationRequest $request)
    {
        $order = Order::query()->findOrFail(session(self::SESSION_CONFIRMATION_ID));

        $this->authorize('view', $order);

        return view('checkout::confirmation', ['order' => $order]);
    }
}
