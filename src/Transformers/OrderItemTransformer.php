<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Transformers;

use Tipoff\Checkout\Models\OrderItem;

class OrderItemTransformer extends BaseItemTransformer
{
    /**
     * @inheritDoc
     */
    public function transform($orderItem)
    {
        /** @var OrderItem $orderItem */
        return array_merge(parent::transform($orderItem), [
            // Cart specific items
        ]);
    }
}
