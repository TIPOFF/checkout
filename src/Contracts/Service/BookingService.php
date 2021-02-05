<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Contracts\Service;

use Tipoff\Checkout\Contracts\Model\SlotInterface;

interface BookingService
{
    public function resolveSlot(string $slotNumber, bool $persist = false): SlotInterface;

    public function createBooking(array $attributes);
}
