<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Currency;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;

class Cart extends BaseCheckoutResource
{
    public static $model = \Tipoff\Checkout\Models\Cart::class;

    public static $title = 'id';

    public static $search = [

    ];

    public static $group = 'Ecommerce';

    /** @psalm-suppress UndefinedClass */
    protected array $filterClassList = [
        \Tipoff\Locations\Nova\Filters\Location::class,
    ];

    public static function indexQuery(NovaRequest $request, $query)
    {
        if ($request->user()->hasPermissionTo('all locations')) {
            return $query;
        }

        return $query->whereIn('location_id', $request->user()->locations->pluck('id'));
    }

    public function fieldsForIndex(NovaRequest $request)
    {
        return array_filter([
            ID::make()->sortable(),
            nova('user') ? BelongsTo::make('Customer', 'user', nova('user'))->sortable() : null,
            nova('location') ? BelongsTo::make('Location', 'location', nova('location'))->sortable() : null,
            Currency::make('Item Total', 'item_amount_total')->asMinorUnits()->sortable(),
            Date::make('Created', 'created_at')->sortable()->exceptOnForms(),
        ]);
    }

    public function fields(Request $request)
    {
        return array_filter([
            Currency::make('Shipping')->asMinorUnits()->min(0)->default(0)->required(),
            Currency::make('Shipping discounts')->asMinorUnits()->min(0)->default(0)->required(),
            Currency::make('Discounts')->asMinorUnits()->min(0)->default(0)->required(),
            Currency::make('Credits')->asMinorUnits()->min(0)->default(0)->required(),
            Currency::make('Item amount total')->asMinorUnits()->exceptOnForms(),
            Currency::make('Item amount total discounts')->asMinorUnits()->exceptOnForms(),
            Currency::make('Tax')->asMinorUnits()->exceptOnForms(),

            nova('address') ? HasMany::make('Addresses', 'addresses', nova('address')) : null,
            nova('discount') ? HasMany::make('Discounts', 'discounts', nova('discount')) : null,
            nova('voucher') ? HasMany::make('Vouchers', 'voucher', nova('voucher')) : null,
            nova('location') ? BelongsTo::make('Location', 'location', nova('location')) : null,
            nova('user') ? BelongsTo::make('User', 'user', nova('user'))->searchable() : null,
            nova('order') ? BelongsTo::make('Order', 'order', nova('order'))->nullable() : null,
            HasMany::make('Cart items', 'cart items', CartItem::class)->nullable(),

            new Panel('Data Fields', $this->dataFields()),
        ]);
    }
}
