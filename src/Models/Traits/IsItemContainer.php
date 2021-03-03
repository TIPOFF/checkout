<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Models\Traits;

use Carbon\Carbon;
use Tipoff\Checkout\Objects\ContainerPricingDetail;
use Tipoff\Support\Contracts\Checkout\BaseItemInterface;
use Tipoff\Support\Contracts\Models\UserInterface;
use Tipoff\Support\Objects\DiscountableValue;
use Tipoff\Support\Traits\HasCreator;
use Tipoff\Support\Traits\HasUpdater;

/**
 * @property int|null id
 * @property DiscountableValue shipping
 * @property int discounts
 * @property int credits
 * @property DiscountableValue $item_amount_total
 * @property int tax
 * @property Carbon created_at
 * @property Carbon updated_at
 * // Relations
 * @property mixed user
 * @property mixed location
 * // Raw Relation ID
 * @property int|null user_id
 * @property int|null location_id
 * @property int|null creator_id
 * @property int|null updater_id
 */
trait IsItemContainer
{
    use HasCreator;
    use HasUpdater;

    protected static function bootIsItemContainer()
    {
        static::saving(function ($model) {
            $model->updateCalculatedValues();
        });
    }

    //region RELATIONSHIPS

    public function user()
    {
        return $this->belongsTo(app('user'));
    }

    public function location()
    {
        return $this->belongsTo(app('location'));
    }

    //endregion

    //region HELPERS

    public function getPricingDetail(): ContainerPricingDetail
    {
        return new ContainerPricingDetail($this);
    }

    protected function updateItemAmountTotal(): self
    {
        $this->item_amount_total = $this->getItems()->reduce(function (DiscountableValue $itemAmountTotal, BaseItemInterface $item) {
            return $itemAmountTotal->add($item->getAmountTotal());
        }, new DiscountableValue(0));

        return $this;
    }

    protected function updateTax(): self
    {
        $this->tax = $this->getItems()->sum->tax;

        return $this;
    }

    protected function updateCalculatedValues(): self
    {
        return $this->updateItemAmountTotal()->updateTax();
    }

    //endregion

    //region INTERFACE IMPLEMENTATION

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function getItemAmountTotal(): DiscountableValue
    {
        return $this->item_amount_total ?? new DiscountableValue(0);
    }

    public function getTax(): int
    {
        return $this->tax ?? 0;
    }

    public function getShipping(): DiscountableValue
    {
        return $this->shipping ?? new DiscountableValue(0);
    }

    public function getDiscounts(): int
    {
        return $this->discounts ?? 0;
    }

    public function getCredits(): int
    {
        return $this->credits ?? 0;
    }

    public function getLocationId(): ?int
    {
        return $this->location_id;
    }

    //endregion
}
