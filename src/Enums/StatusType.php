<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Enums;

use Tipoff\Support\Enums\BaseEnum;

/**
 * @method static StatusTypes ORDER()
 * @psalm-immutable
 */
class StatusType extends BaseEnum
{
    const ORDER = 'order';
}
