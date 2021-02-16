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

    ];

    public function fieldsForIndex(NovaRequest $request)
    {
        return array_filter([
            ID::make()->sortable(),
            Text::make('Order Number')->sortable(),
            nova('customer') ? BelongsTo::make('Customer', 'customer', nova('customer'))->sortable() : null,
            nova('location') ? BelongsTo::make('Location', 'location', nova('location'))->sortable() : null,
            Currency::make('Amount')->asMinorUnits()->sortable(),
            Date::make('Created', 'created_at')->sortable()->exceptOnForms(),
        ]);
    }

    public function fields(Request $request)
    {
        return array_filter([
            Text::make('Order Number')->exceptOnForms(),
            nova('customer') ? BelongsTo::make('Customer', 'customer', nova('customer'))->searchable()->withSubtitles() : null,
            nova('location') ? BelongsTo::make('Location', 'location', nova('location')) : null,
            Currency::make('Amount')->asMinorUnits()->exceptOnForms(),
            Currency::make('Taxes', 'total_taxes')->asMinorUnits()->exceptOnForms(),
            Currency::make('Fees', 'total_fees')->asMinorUnits()->exceptOnForms(),
            nova('booking') ? HasMany::make('Bookings', 'bookings', nova('booking')) : null,
            nova('voucher') ? HasMany::make('Purchased Vouchers', 'purchasedVouchers', nova('voucher')) : null,
            nova('payment') ? HasMany::make('Payments', 'payments', nova('payment')) : null,
            nova('invoice') ? HasMany::make('Invoices', 'invoices', nova('invoice')) : null,
            nova('discount') ? HasMany::make('Discounts', 'discounts', nova('discount')) : null,
            nova('voucher') ? HasMany::make('Vouchers', 'voucher', nova('voucher')) : null,
            nova('note') ? MorphMany::make('Notes', 'notes', nova('note')) : null,
            new Panel('Data Fields', $this->dataFields()),
        ]);
    }

    protected function dataFields()
    {
        return array_merge(
            parent::dataFields(),
            $this->creatorDataFields(),
            [
                Date::make('Updated At')->exceptOnForms(),
            ],
        );
    }
}
