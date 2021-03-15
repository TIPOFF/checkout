<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Exceptions;

use Throwable;

class PaymentNotAvailableException extends \InvalidArgumentException implements CheckoutException
{
    public function __construct($code = 0, Throwable $previous = null)
    {
        parent::__construct('Payment services are not enabled.', $code, $previous);
    }
}
