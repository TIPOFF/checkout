<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Contracts\Service;

use Tipoff\Checkout\Contracts\Model\CartInterface;

interface VouchersService extends CartDeduction
{
    public function generateVoucherCode(): string;

    public function applyCodeToCart(CartInterface $cart, string $code): bool;

    public function issuePartialRedemptionVoucher(CartInterface $cart, int $amount): int;

    public function markVouchersAsUsed(CartInterface $cart, int $orderId);
}
