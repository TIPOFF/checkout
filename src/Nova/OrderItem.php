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

class OrderItem extends BaseCheckoutResource
{
    public static $model = \Tipoff\Checkout\Models\OrderItem::class;

    public static $title = 'item_id';

    public static $search = [
        'item_id',
        'description',
    ];

    public static $group;

    public function fieldsForIndex(NovaRequest $request)
    {
        return [
            ID::make()->sortable(),
            BelongsTo::make('Order', 'order', Order::class)->sortable(),
            Text::make('Item ID', 'item_id')->sortable(),
            Number::make('Quantity')->sortable(),
            Currency::make('Amount Each', 'amount_each')->asMinorUnits()->sortable(),
            Currency::make('Discount Each', 'amount_each_discounts')->asMinorUnits()->sortable(),
            Currency::make('Amount Total', 'amount_total')->asMinorUnits()->sortable(),
            Currency::make('Discount Total', 'amount_total_discounts')->asMinorUnits()->sortable(),
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
            Currency::make('Amount Each', 'amount_each')->asMinorUnits()->exceptOnForms(),
            Currency::make('Discount Each', 'amount_each_discounts')->asMinorUnits()->exceptOnForms(),
            Currency::make('Amount Total', 'amount_total')->asMinorUnits()->exceptOnForms(),
            Currency::make('Discount Total', 'amount_total_discounts')->asMinorUnits()->exceptOnForms(),
            Currency::make('Taxes', 'tax')->asMinorUnits()->exceptOnForms(),
            Text::make('Tax Code', 'tax_code')->exceptOnForms(),
            new Panel('Data Fields', $this->dataFields()),
        ]);
    }
}
