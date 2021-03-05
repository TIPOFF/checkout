<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Currency;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\MorphMany;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;
use Tipoff\Support\Nova\BaseResource;

class Order extends BaseResource
{
    public static $model = \Tipoff\Checkout\Models\Order::class;

    public static $title = 'order_number';

    public static $search = [
        'order_number',
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

    /** @psalm-suppress UndefinedClass */
    protected array $filterClassList = [
        \Tipoff\Locations\Nova\Filters\Location::class,
    ];

    public function fieldsForIndex(NovaRequest $request)
    {
        return array_filter([
            ID::make()->sortable(),
            Text::make('Order Number')->sortable(),
            nova('user') ? BelongsTo::make('Customer', 'user', nova('user'))->sortable() : null,
            nova('location') ? BelongsTo::make('Location', 'location', nova('location'))->sortable() : null,
            Currency::make('Item Total', 'item_amount_total')->asMinorUnits()->sortable(),
            Date::make('Created', 'created_at')->sortable()->exceptOnForms(),
        ]);
    }

    public function fields(Request $request)
    {
        return array_filter([
            Text::make('Order Number')->exceptOnForms(),
            nova('user') ? BelongsTo::make('Customer', 'user', nova('user'))->searchable()->withSubtitles() : null,
            nova('location') ? BelongsTo::make('Location', 'location', nova('location')) : null,
            Currency::make('Item Total', 'item_amount_total')->asMinorUnits()->exceptOnForms(),
            Currency::make('Item Total Discounts', 'item_amount_total_discounts')->asMinorUnits()->exceptOnForms(),
            Currency::make('Taxes', 'tax')->asMinorUnits()->exceptOnForms(),
            HasMany::make('Items', 'orderItems', OrderItem::class),
            nova('payment') ? HasMany::make('Payments', 'payments', nova('payment')) : null,
            nova('invoice') ? HasMany::make('Invoices', 'invoices', nova('invoice')) : null,
            nova('discount') ? HasMany::make('Discounts', 'discounts', nova('discount')) : null,
            nova('voucher') ? HasMany::make('Vouchers', 'voucher', nova('voucher')) : null,
            nova('note') ? MorphMany::make('Notes', 'notes', nova('note')) : null,
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
