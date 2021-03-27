<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Exceptions;

use Throwable;

class CartNotValidException extends \InvalidArgumentException implements CheckoutException
{
    public static function cartIsEmpty($code = 0, Throwable $previous = null): self
    {
        return new static('Cart is empty.', $code, $previous);
    }

    public static function cartHasExpiredItems($code = 0, Throwable $previous = null): self
    {
        return new static('Cart contains 1 or more expired items.', $code, $previous);
    }

    public static function noUserExists($code = 0, Throwable $previous = null): self
    {
        return new static('Email address is not associated with a user.', $code, $previous);
    }

    public static function pricingHasChanged($code = 0, Throwable $previous = null): self
    {
        return new static('Cart pricing has changed.', $code, $previous);
    }

    public static function itemException(\Throwable $ex): self
    {
        return new static($ex->getMessage(), $ex->getCode(), $ex);
    }

    public function __construct(?string $message = null, $code = 0, Throwable $previous = null)
    {
        parent::__construct($message ?? 'Cart not valid.', $code, $previous);
    }
}
