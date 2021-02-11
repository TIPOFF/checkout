<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Contracts\Models;

use Tipoff\Support\Contracts\Models\BaseModelInterface;

interface VoucherInterface extends BaseModelInterface, CartDeduction
{
    public static function generateVoucherCode(): string;

    public static function issuePartialRedemptionVoucher(int $locationId, int $amount, int $userId): VoucherInterface;
}
