<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Listeners;

use Illuminate\Auth\Events\Login;
use Tipoff\Authorization\Traits\UsesTipoffAuthentication;
use Tipoff\Checkout\Models\Cart;

class LoginListener
{
    use UsesTipoffAuthentication;

    public function handle(Login $event): void
    {
        if ($emailAddressId = $this->getEmailAddressId()) {
            Cart::dequeueCartItem($emailAddressId);
        }
    }
}
