<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate;

class CartAuthenticate extends Authenticate
{
    protected function redirectTo($request)
    {
        return route('checkout.cart-create');
    }
}
