<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Code;
use Laravel\Nova\Fields\Currency;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\HasOne;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\MorphTo;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;

class CartItem extends BaseCheckoutResource
{
    public static $model = \Tipoff\Checkout\Models\CartItem::class;

    public static $title = 'item_id';

    public static $search = [

    ];

    public static $group = 'Ecommerce';

    /** @psalm-suppress UndefinedClass */
    protected array $filterClassList = [

    ];

    public static function indexQuery(NovaRequest $request, $query)
    {
        if ($request->user()->hasRole([
            'Admin',
            'Owner',
            'Executive',
        ])) {
            return $query;
        }

        return $query->whereIn('location_id', $request->user()->locations->pluck('id'));
    }

    public function fieldsForIndex(NovaRequest $request)
    {
        return array_filter([
            ID::make()->sortable(),
            BelongsTo::make('Cart', 'Cart', Cart::class)->sortable(),
            Text::make('Item ID', 'item_id')->sortable(),
            Number::make('Quantity')->sortable(),
            Currency::make('Amount Each', 'amount_each')->asMinorUnits()->sortable(),
            Currency::make('Discount Each', 'amount_each_discounts')->asMinorUnits()->sortable(),
            Currency::make('Amount Total', 'amount_total')->asMinorUnits()->sortable(),
            Currency::make('Discount Total', 'amount_total_discounts')->asMinorUnits()->sortable(),
            Currency::make('Taxes', 'tax')->asMinorUnits()->sortable(),
        ]);
    }

    public function fields(Request $request)
    {
        return array_filter([
            BelongsTo::make('Cart', 'cart', Cart::class),
            MorphTo::make('sellable')->types(array_filter([
                nova('booking'),
                nova('product'),
                nova('fee'),
                nova('voucher_type'),
            ])),
            nova('location') ? BelongsTo::make('Location', 'location', nova('location')) : null,
            Text::make('Item id')->required(),
            Text::make('Description')->required(),
            Number::make('Quantity')->required()->min(1),
            Currency::make('Amount each')->asMinorUnits()->required()->min(0)->default(0),
            Currency::make('Amount each discounts')->asMinorUnits()->required()->min(0)->default(0),
            Currency::make('Amount total')->asMinorUnits()->exceptOnForms(),
            Currency::make('Amount total discounts')->asMinorUnits()->exceptOnForms(),
            Currency::make('Taxes', 'tax')->asMinorUnits()->required()->min(0)->default(0),
            DateTime::make('Expires at')->required(),
            Text::make('Tax code')->nullable(),

            Code::make('Meta data')->json()->nullable(),
            HasOne::make('Parent Item', 'parent', CartItem::class)->nullable(),

            new Panel('Data Fields', $this->dataFields()),
        ]);
    }
}
