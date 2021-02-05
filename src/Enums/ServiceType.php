<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Enums;

use Illuminate\Support\Str;
use MabeEnum\Enum;
use Tipoff\Checkout\Contracts\Service\NullService;

/**
 * @method static ServiceType DISCOUNT()
 * @method static ServiceType VOUCHER()
 * @method static ServiceType BOOKING()
 * @method static ServiceType TAX()
 * @method static ServiceType FEE()
 *
 * @psalm-immutable
 */
class ServiceType extends Enum
{
    const DISCOUNT = 'discount';
    const VOUCHER = 'voucher';
    const BOOKING = 'booking';
    const TAX = 'tax';
    const FEE = 'fee';

    /** @psalm-suppress ImpureFunctionCall */
    public function enabled(): bool
    {
        return app((string) $this->getValue()) instanceof NullService ? false : true;
    }

    /** @psalm-suppress ImpureMethodCall */
    public function title(): string
    {
        return (string) Str::of((string) $this->getValue())->snake()->replace('_', ' ')->title();
    }
}
