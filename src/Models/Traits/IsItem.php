<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Models\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Tipoff\Support\Contracts\Sellable\Sellable;
use Tipoff\Support\Objects\DiscountableValue;
use Tipoff\Support\Traits\HasCreator;
use Tipoff\Support\Traits\HasUpdater;

/**
 * @property int|null id
 * @property string item_id
 * @property Sellable sellable
 * @property string description
 * @property int quantity
 * @property DiscountableValue amount_total
 * @property DiscountableValue amount_each
 * @property int tax
 * @property string|null tax_code
 * @property array|null meta_data
 * @property Carbon created_at
 * @property Carbon updated_at
 * // Relations
 * @property mixed parent
 * @property mixed location
 * @property Collection children
 * // Raw Relation ID
 * @property int|null parent_id
 * @property int|null sellable_id
 * @property string|null sellable_type
 * @property int|null location_id
 * @property int|null creator_id
 * @property int|null updater_id
 */
trait IsItem
{
    use HasCreator;
    use HasUpdater;

    protected static function bootIsItem()
    {
        static::saving(function ($model) {
            $model->updateCalculatedValues();
        });
    }

    //region RELATIONSHIPS

    public function parent()
    {
        return $this->belongsTo(static::class);
    }

    public function children()
    {
        return $this->hasMany(static::class, 'parent_id', 'id');
    }

    public function sellable()
    {
        return $this->morphTo();
    }

    public function location()
    {
        return $this->belongsTo(app('location'));
    }

    //endregion

    //region SCOPES

    public function scopeBySellableId(Builder $query, Sellable $sellable, string $itemId): Builder
    {
        return $query->where(function ($query) use ($sellable, $itemId) {
            $query->where('sellable_type', '=', $sellable->getMorphClass());
            $query->where('item_id', '=', $itemId);
        });
    }

    public function scopeIsRootItem(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeIsChildItem(Builder $query): Builder
    {
        return $query->whereNotNull('parent_id');
    }

    //endregion

    public function delete()
    {
        // Delete children - this is recursive
        $this->children()->get()->each->delete();

        return parent::delete();
    }

    protected function updateAmountTotal(): self
    {
        $this->amount_total = $this->getAmountEach()->multiply($this->getQuantity());

        return $this;
    }

    public function updateCalculatedValues(): self
    {
        return $this->updateAmountTotal();
    }

    //region INTERFACE IMPLEMENTATION

    public function getSellable(): Sellable
    {
        return $this->sellable;
    }

    public function setSellable(Sellable $sellable)
    {
        $this->sellable()->associate($sellable);

        return $this;
    }

    public function getParentItem()
    {
        return $this->parent;
    }

    public function getRootItem()
    {
        $rootItem = $this->getParentItem();
        while ($rootItem && $rootItem->getParentItem()) {
            $rootItem = $rootItem->getParentItem();
        }

        return $rootItem;
    }

    public function getItemId(): string
    {
        return $this->item_id;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getAmountEach(): DiscountableValue
    {
        return $this->amount_each ?? new DiscountableValue(0);
    }

    public function getAmountTotal(): DiscountableValue
    {
        return $this->amount_total ?? new DiscountableValue(0);
    }

    public function getTax(): int
    {
        return $this->tax ?? 0;
    }

    public function getLocationId(): ?int
    {
        return $this->location_id;
    }

    public function getTaxCode(): ?string
    {
        return $this->tax_code;
    }

    public function getMetaData(?string $key, $default = null)
    {
        return Arr::get($this->meta_data, $key, $default);
    }

    public function setMetaData(?string $key, $value)
    {
        $metaData = $this->meta_data ?? [];
        $this->meta_data = Arr::set($metaData, $key, $value);

        return $this;
    }

    //endregion
}
