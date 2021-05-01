<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Nova;

use Laravel\Nova\Http\Requests\NovaRequest;
use Tipoff\Support\Nova\BaseResource;

abstract class BaseCheckoutResource extends BaseResource
{
    public static $group = 'Operations';

    public static function indexQuery(NovaRequest $request, $query)
    {
        if ($request->user()->hasPermissionTo('all locations')) {
            return $query;
        }

        return $query->whereIn('location_id', $request->user()->locations->pluck('id'));
    }

    protected function dataFields(): array
    {
        return array_merge(
            parent::dataFields(),
            $this->creatorDataFields(),
            $this->updaterDataFields()
        );
    }
}
