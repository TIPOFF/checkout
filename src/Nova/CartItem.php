<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;
use Tipoff\Support\Nova\BaseResource;

class CartItem extends BaseResource
{
    public static $model = \Tipoff\Checkout\Models\CartItem::class;

    public static $title = 'item_id';

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
            // Missing columns for $table->morphs('sellable')
            Text::make('Item id')->required(),
            Text::make('Description')->required(),
            Number::make('Quantity')->required()->min(1),
            Number::make('Amount each')->required()->min(0)->default(0),
            Number::make('Amount each discounts')->required()->min(0)->default(0),
            Number::make('Amount total')->required()->min(0)->default(0),
            Number::make('Amount total discounts')->required()->min(0)->default(0),
            Number::make('Tax')->required()->min(0)->default(0),
            DateTime::make('Expires at')->required(),
            Number::make('Location id')->min(0)->nullable(),
            Text::make('Tax code')->nullable(),
            // $table->json('meta_data')->nullable();
            Text::make('Meta data')->nullable(),

            nova('cart') ? BelongsTo::make('Cart', 'cart', nova('cart'))->searchable() : null,
            // In migration, parent_id
            nova('cart-item') ? BelongsTo::make('Cart item', 'cart item', nova('cart-item'))->nullable() : null,

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
