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

    public function fieldsForIndex(NovaRequest $request)
    {
        return [
            ID::make()->sortable(),
            Text::make('Order Number')->sortable(),
            BelongsTo::make('Customer', 'customer', app()->getAlias('nova.customer'))->sortable(),
            BelongsTo::make('Location', 'location', app()->getAlias('nova.location'))->sortable(),
            Currency::make('Amount')->asMinorUnits()->sortable(),
            Date::make('Created', 'created_at')->sortable()->exceptOnForms(),
        ];
    }

    public function fields(Request $request)
    {
        return [
            Text::make('Order Number')->exceptOnForms(),
            BelongsTo::make('Customer', 'customer', app()->getAlias('nova.customer'))->searchable()->withSubtitles(),
            BelongsTo::make('Location', 'location', app()->getAlias('nova.location')),
            Currency::make('Amount')->asMinorUnits()->exceptOnForms(),
            Currency::make('Taxes', 'total_taxes')->asMinorUnits()->exceptOnForms(),
            Currency::make('Fees', 'total_fees')->asMinorUnits()->exceptOnForms(),
            HasMany::make('Bookings', 'bookings', app()->getAlias('nova.booking')),
            HasMany::make('Purchased Vouchers', 'purchasedVouchers', app()->getAlias('nova.voucher')),
            HasMany::make('Payments', 'payments', app()->getAlias('nova.payment')),
            HasMany::make('Invoices', 'invoices', app()->getAlias('nova.invoice')),
            HasMany::make('Discounts', 'discounts', app()->getAlias('nova.discount')),
            HasMany::make('Vouchers', 'voucher', app()->getAlias('nova.voucher')),
            MorphMany::make('Notes', 'notes', app()->getAlias('nova.note')),
            new Panel('Data Fields', $this->dataFields()),
        ];
    }

    protected function dataFields(): array
    {
        return [
            ID::make(),
            BelongsTo::make('Creator', 'creator', app()->getAlias('nova.user'))->exceptOnForms(),
            Date::make('Created At')->exceptOnForms(),
            Date::make('Updated At')->exceptOnForms(),
        ];
    }

    public function cards(Request $request)
    {
        return [];
    }

    public function filters(Request $request)
    {
        return [
            // TODO - resolve how these can be shared across packages
            // new Filters\Location,
        ];
    }

    public function lenses(Request $request)
    {
        return [];
    }

    public function actions(Request $request)
    {
        return [];
    }
}
