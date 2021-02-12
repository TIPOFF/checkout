<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Contracts\Models;

use Tipoff\Support\Contracts\Models\BaseModelInterface;

interface OrderInterface extends BaseModelInterface
{
    public function getCustomerId(): ?int;

    public function getOrderNumber(): string;

    // TODO - typehint to LocationInterface when available
    public function getLocation();
}
