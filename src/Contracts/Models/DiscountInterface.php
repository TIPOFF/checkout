<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Contracts\Models;

use Tipoff\Support\Contracts\Models\BaseModelInterface;

interface DiscountInterface extends BaseModelInterface, CartDeduction
{
}
