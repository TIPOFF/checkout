<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Contracts\Models;

use Tipoff\Support\Contracts\Models\BaseModelInterface;

interface CartInterface extends BaseModelInterface
{
    public function applyDeductionCode(string $code): CartInterface;

    public function getTotalParticipants(): int;
}
