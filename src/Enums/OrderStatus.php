<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Enums;

use Tipoff\Statuses\Models\Status;
use Tipoff\Support\Enums\BaseEnum;

/**
 * @method static OrderStatus PROCESSING()
 * @method static OrderStatus SHIPPING()
 * @method static OrderStatus DELIVERING()
 * @method static OrderStatus REALIZING()
 * @method static OrderStatus COMPLETE()
 * @psalm-immutable
 */
class OrderStatus extends BaseEnum
{
    const PROCESSING = 'Processing';
    const SHIPPING = 'Shipping';
    const DELIVERING = 'Delivering';
    const REALIZING = 'Realizing';
    const COMPLETE = 'Complete';

    public static function statusType(): string
    {
        return StatusType::ORDER;
    }

    public function toStatus(): ?Status
    {
        /** @psalm-suppress ImpureMethodCall */
        return Status::findStatus(static::statusType(), (string) $this->getValue());
    }
}
