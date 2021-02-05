<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Services;

use Tipoff\Checkout\Contracts\Model\SlotInterface;
use Tipoff\Checkout\Contracts\Service\NullService;
use Tipoff\Checkout\Contracts\Service\BookingService;
use Tipoff\Checkout\Enums\ServiceType;
use Tipoff\Checkout\Exceptions\ServiceNotImplementedException;

class NullBookingService implements BookingService, NullService
{
    public function createBooking(array $attributes)
    {
        // TODO: Implement createBooking() method.
    }

    public function resolveSlot(string $slotNumber, bool $persist = false): SlotInterface
    {
        throw new ServiceNotImplementedException(ServiceType::BOOKING());
    }
}
