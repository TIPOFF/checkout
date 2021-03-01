<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Currency;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\MorphTo;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;
use Tipoff\Support\Nova\BaseResource;

class OrderItem extends BaseResource
{
    public static $model = \Tipoff\Checkout\Models\OrderItem::class;

    public static $title = 'item_id';

    public static $search = [
        'item_id',
        'description',
    ];

    public static function indexQuery(NovaRequest $request, $query)
    {
        if ($request->user()->hasRole([
            'Admin',
            'Owner',
            'Accountant',
            'Executive',
            'Reservation Manager',
            'Reservationist',
        ])) {
            return $query;
        }

        return $query->whereIn('location_id', $request->user()->locations->pluck('id'));
    }

    public static $group = 'Operations';

    public function fieldsForIndex(NovaRequest $request)
    {
        return [
            ID::make()->sortable(),
            BelongsTo::make('Order', 'order', Order::class)->sortable(),
            Text::make('Item ID', 'item_id')->sortable(),
            Number::make('Quantity')->sortable(),
            Currency::make('Amount')->asMinorUnits()->sortable(),
            Currency::make('Discount', 'amount_discounts')->asMinorUnits()->sortable(),
            Currency::make('Taxes', 'tax')->asMinorUnits()->sortable(),
        ];
    }

    public function fields(Request $request)
    {
        return array_filter([
            BelongsTo::make('Order', 'order', Order::class),
            MorphTo::make('sellable'),
            nova('location') ? BelongsTo::make('Location', 'location', nova('location')) : null,
            Text::make('Item ID', 'item_id')->exceptOnForms(),
            Number::make('Quantity')->exceptOnForms(),
            Currency::make('Amount')->asMinorUnits()->exceptOnForms(),
            Currency::make('Discount', 'amount_discounts')->asMinorUnits()->exceptOnForms(),
            Currency::make('Taxes', 'tax')->asMinorUnits()->exceptOnForms(),
            Text::make('Tax Code', 'tax_code')->exceptOnForms(),
            new Panel('Data Fields', $this->dataFields()),
        ]);
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
