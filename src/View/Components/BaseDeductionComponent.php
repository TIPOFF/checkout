<?php

declare(strict_types=1);

namespace Tipoff\Checkout\View\Components;

use Illuminate\View\Component;
use Tipoff\Support\Contracts\Checkout\CodedCartAdjustment;

abstract class BaseDeductionComponent extends Component
{
    public CodedCartAdjustment $deduction;

    public function __construct(CodedCartAdjustment $deduction)
    {
        $this->deduction = $deduction;
    }
}
