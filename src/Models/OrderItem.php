<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Models;

use Tipoff\Checkout\Models\Traits\IsItem;
use Tipoff\Support\Contracts\Checkout\OrderInterface;
use Tipoff\Support\Contracts\Checkout\OrderItemInterface;
use Tipoff\Support\Models\BaseModel;
use Tipoff\Support\Traits\HasPackageFactory;

class OrderItem extends BaseModel implements OrderItemInterface
{
    use HasPackageFactory;
    use IsItem;

    protected $casts = [
        'id' => 'integer',
        'amount' => \Tipoff\Support\Casts\DiscountableValue::class,
        'quantity' => 'integer',
        'tax' => 'integer',
        'meta_data' => 'json',
        // Raw relation id access
        'sellable_id' => 'integer',
        'order_id' => 'integer',
        'parent_id' => 'integer',
        'location_id' => 'integer',
        'creator_id' => 'integer',
        'updater_id' => 'integer',
    ];

    //region RELATIONSHIPS

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    //endregion

    //region INTERFACE IMPLEMENTATION

    public function getOrder(): ?OrderInterface
    {
        return $this->order;
    }

    //endregion
}
