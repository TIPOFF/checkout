<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Filters;

use Tipoff\Locations\Models\Location;
use Tipoff\Support\Contracts\Checkout\Filters\ItemFilter as ItemFilterContract;

class ItemFilter extends Filter implements  ItemFilterContract
{
    /**
     * @param Location|int $location
     * @return $this
     */
    public function byLocation($location): self
    {
        $this->query = $this->query->byLocationId($location instanceof Location ? $location->getId() : (int) $location);

        return $this;
    }

    public function bySellableType(string $sellableType, bool $includeChildren = true): self
    {
        $this->query = $this->query->bySellableType($sellableType, $includeChildren);

        return $this;
    }

    public function byItemId(string $itemId): self
    {
        $this->query = $this->query->byItemId($itemId);

        return $this;
    }
}
