<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Exceptions;

use Throwable;

class MultipleLocationException extends \InvalidArgumentException implements CheckoutException
{
    public function __construct($code = 0, Throwable $previous = null)
    {
        parent::__construct('Cart must contain items from single location.', $code, $previous);
    }
}
