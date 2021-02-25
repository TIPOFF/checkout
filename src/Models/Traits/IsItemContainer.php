<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Models\Traits;

use Carbon\Carbon;
use Tipoff\Support\Objects\DiscountableValue;
use Tipoff\Support\Traits\HasCreator;
use Tipoff\Support\Traits\HasUpdater;

/**
 * @property int|null id
 * @property DiscountableValue $shipping
 * @property int discounts
 * @property DiscountableValue $item_amount
 * @property int tax
 * @property Carbon created_at
 * @property Carbon updated_at
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

    //region INTERFACE IMPLEMENTATION

    public function getItemAmount(): DiscountableValue
    {
        return $this->item_amount;
    }

    public function getTax(): int
    {
        return $this->tax;
    }

    public function getShipping(): DiscountableValue
    {
        return $this->shipping;
    }

    public function getDiscounts(): int
    {
        return $this->discounts;
    }

    public function getLocationId(): ?int
    {
        return $this->location_id;
    }

    //endregion
}
