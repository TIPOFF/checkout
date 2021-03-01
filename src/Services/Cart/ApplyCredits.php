<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Services\Cart;

use Tipoff\Checkout\Models\Cart;
use Tipoff\Support\Contracts\Checkout\Vouchers\VoucherInterface;

class ApplyCredits
{
    public function __invoke(Cart $cart): Cart
    {
        if ($service = findService(VoucherInterface::class)) {
            /** @var VoucherInterface $service */
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
