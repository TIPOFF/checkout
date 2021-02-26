<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Services\Cart;

use Illuminate\Support\Facades\DB;
use Tipoff\Checkout\Exceptions\CartNotValidException;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Support\Contracts\Checkout\Vouchers\VoucherInterface;
use Tipoff\Support\Events\Checkout\CartItemPurchaseVerification;

class ApplyCredits
{
    public function __invoke(Cart $cart): Cart
    {
        if ($service = findService(VoucherInterface::class)) {
            $this->resetCredits($cart)->calculateAdjustments($service, $cart);
        }

        return $cart;
    }

    private function resetCredits(Cart $cart): self
    {
        $cart->credits = 0;

        return $this;
    }

    private function calculateAdjustments(VoucherInterface $service, Cart $cart): self
    {
        $service::calculateAdjustments($cart);

        return $this;
    }

}
