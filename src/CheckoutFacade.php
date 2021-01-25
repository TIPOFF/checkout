<?php

namespace Tipoff\Checkout;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Tipoff\Checkout\Checkout
 */
class CheckoutFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'checkout';
    }
}
