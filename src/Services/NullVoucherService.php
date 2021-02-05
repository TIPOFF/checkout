<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Services;

use Brick\Money\Money;
use Tipoff\Checkout\Contracts\Model\CartInterface;
use Tipoff\Checkout\Contracts\Service\NullService;
use Tipoff\Checkout\Contracts\Service\VouchersService;
use Tipoff\Checkout\Enums\ServiceType;
use Tipoff\Checkout\Exceptions\ServiceNotImplementedException;

class NullVoucherService implements VouchersService, NullService
{

    public function generateVoucherCode(): string
    {
        throw new ServiceNotImplementedException(ServiceType::VOUCHER());
    }

    public function applyCodeToCart(CartInterface $cart, string $code): bool
    {
        return false;
    }

    public function xcalculateDeductions(CartInterface $cart): Money
    {
        return Money::ofMinor(0, 'USD');
    }

    public function issuePartialRedemptionVoucher(CartInterface $cart, int $amount): int
    {
        // TODO: Implement issuePartialRedemptionVoucher() method.
    }

    public function markVouchersAsUsed(CartInterface $cart, int $orderId)
    {
    }
}
