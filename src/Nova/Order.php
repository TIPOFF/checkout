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
use Tipoff\Support\Nova\Resource;

class Order extends Resource
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
            BelongsTo::make('Customer', 'customer', config('checkout.nova_class.customer'))->sortable(),
            BelongsTo::make('Location', 'location', config('checkout.nova_class.location'))->sortable(),
            Currency::make('Amount')->asMinorUnits()->sortable(),
            Date::make('Created', 'created_at')->sortable()->exceptOnForms(),
        ];
    }

    public function fields(Request $request)
    {
        return [
            Text::make('Order Number')->exceptOnForms(),
            BelongsTo::make('Customer', 'customer', config('checkout.nova_class.customer'))->searchable()->withSubtitles(),
            BelongsTo::make('Location', 'location', config('checkout.nova_class.location')),
            Currency::make('Amount')->asMinorUnits()->exceptOnForms(),
            Currency::make('Taxes', 'total_taxes')->asMinorUnits()->exceptOnForms(),
            Currency::make('Fees', 'total_fees')->asMinorUnits()->exceptOnForms(),
            HasMany::make('Bookings', 'bookings', config('checkout.nova_class.booking')),
            HasMany::make('Purchased Vouchers', 'purchasedVouchers', config('checkout.nova_class.voucher')),
            HasMany::make('Payments', 'payments', config('checkout.nova_class.payment')),
            HasMany::make('Invoices', 'invoices', config('checkout.nova_class.invoice')),
            HasMany::make('Discounts', 'discounts', config('checkout.nova_class.discount')),
            HasMany::make('Vouchers', 'voucher', config('checkout.nova_class.voucher')),
            MorphMany::make('Notes', 'notes', config('checkout.nova_class.note')),
            new Panel('Data Fields', $this->dataFields()),
        ];
    }

    protected function dataFields()
    {
        return [
            ID::make(),
            BelongsTo::make('Creator', 'creator', config('checkout.nova_class.user'))->exceptOnForms(),
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
