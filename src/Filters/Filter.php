<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Filters;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Tipoff\Support\Contracts\Checkout\Filters\CreatedAtFilter;

abstract class Filter implements CreatedAtFilter
{
    protected Builder $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    public function apply(): Collection
    {
        /** @var Collection $result */
        $result = $this->query->get();

        return $result;
    }

    /**
     * @param Carbon|string $startDate
     * @return $this
     */
    public function byStartDate($startDate): self
    {
        $this->query = $this->query->byStartDate(is_string($startDate) ? Carbon::parse($startDate) : $startDate);

        return $this;
    }

    /**
     * @param Carbon|string $endDate
     * @return $this
     */
    public function byEndDate($endDate): self
    {
        $this->query = $this->query->byEndDate(is_string($endDate) ? Carbon::parse($endDate) : $endDate);

        return $this;
    }

    public function yesterday(): self
    {
        return $this
            ->byStartDate(self::startOfDay()->subDays(1))
            ->byEndDate(self::startOfDay());
    }

    public function yesterdayComparison(): self
    {
        return $this
            ->byStartDate(self::startOfDay()->subDays(8))
            ->byEndDate(self::startOfDay()->subDays(7));
    }

    public function week(): self
    {
        return $this
            ->byStartDate(self::startOfDay()->subDays(7))
            ->byEndDate(self::startOfDay());
    }

    public function weekComparison(): self
    {
        return $this
            ->byStartDate(self::startOfDay()->subDays(14))
            ->byEndDate(self::startOfDay()->subDays(7));
    }

    private static function startOfDay(): Carbon
    {
        return Carbon::now('America/New_York')->startOfDay()->setTimezone('UTC');
    }
}
