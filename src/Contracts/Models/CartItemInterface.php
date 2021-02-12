<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Contracts\Models;

use Brick\Money\Money;
use Tipoff\Support\Contracts\Models\BaseModelInterface;

interface CartItemInterface extends BaseModelInterface
{
    public function getSlotNumber(): ?string;

    public function getIsPrivate(): bool;

    public function getParticipants(): int;

    public function getAmount(): Money;

    public function getTotalFees(): Money;

    public function getTotalDeductions(): Money;

    // TODO - typehint return value to FeeInterface when available
    public function getFee();
}
