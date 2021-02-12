<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Exceptions;

use Throwable;

class InvalidDeductionCodeException extends \InvalidArgumentException implements CheckoutException
{
    public function __construct(string $deductionCode, $code = 0, Throwable $previous = null)
    {
        parent::__construct("Code {$deductionCode} is invalid.", $code, $previous);
    }
}
