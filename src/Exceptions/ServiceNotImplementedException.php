<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Exceptions;

use Throwable;
use Tipoff\Checkout\Enums\ServiceType;

class ServiceNotImplementedException extends \Exception implements CheckoutException
{
    public function __construct(ServiceType $type, $code = 0, Throwable $previous = null)
    {
        parent::__construct("{$type->title()} services are not registered.", $code, $previous);
    }
}
