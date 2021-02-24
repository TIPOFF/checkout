<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Contracts\Models;

use Tipoff\Support\Contracts\Checkout\CartInterface;
use Tipoff\Support\Contracts\Models\BaseModelInterface;

interface VoucherInterface extends BaseModelInterface, CartDeduction
{
    public static function generateVoucherCode(): string;

    public static function issuePartialRedemptionVoucher(CartInterface $cart, int $locationId, int $amount, int $userId): VoucherInterface;
}
