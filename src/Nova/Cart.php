<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;
use Tipoff\Support\Nova\BaseResource;

class Cart extends BaseResource
{
    public static $model = \Tipoff\Checkout\Models\Cart::class;

    public static $title = 'id';

    public static $search = [

    ];

    public static function indexQuery(NovaRequest $request, $query)
    {

    }

    public static $group;

    /** @psalm-suppress UndefinedClass */
    protected array $filterClassList = [

    ];

    public function fieldsForIndex(NovaRequest $request)
    {
        return array_filter([
            ID::make()->sortable(),
        ]);
    }

    public function fields(Request $request)
    {
        return array_filter([
            Number::make('Shipping')->required()->min(0)->default(0),
            Number::make('Shipping discounts')->required()->min(0)->default(0),
            Number::make('Discounts')->required()->min(0)->default(0),
            Number::make('Credits')->required()->min(0)->default(0),
            Number::make('Item amount total')->required()->min(0)->default(0),
            Number::make('Item amount total discounts')->required()->min(0)->default(0),
            Number::make('Tax')->required()->min(0)->default(0),
            Number::make('Location id')->min(0)->nullable(),

            nova('user') ? BelongsTo::make('User', 'user', nova('user'))->searchable() : null,
            nova('order') ? BelongsTo::make('Order', 'order', nova('order'))->nullable() : null,
            nova('cart-item') ? HasMany::make('Cart items', 'cart items', nova('cart-item'))->nullable() : null,

            new Panel('Data Fields', $this->dataFields()),
        ]);
    }

    protected function dataFields(): array
    {
        return array_merge(
            parent::dataFields(),
            [
                $this->creatorDataFields(),
                $this->updaterDataFields(),
            ]
        );
    }
}
