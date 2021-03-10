<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Enums;

use Tipoff\Support\Enums\BaseEnum;

/**
 * @method static AddressTypes SHIPPING()
 * @method static AddressTypes BILLING()
 * @psalm-immutable
 */
class AddressTypes extends BaseEnum
{
    const SHIPPING = 'shipping';
    const BILLING = 'billing';
}
