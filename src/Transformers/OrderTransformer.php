<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Transformers;

use Tipoff\Checkout\Models\Order;
use Tipoff\Support\Transformers\BaseTransformer;

class OrderTransformer extends BaseItemContainerTransformer
{
    public function getItemTransformer(): BaseTransformer
    {
        return new OrderItemTransformer();
    }

    /**
     * @inheritDoc
     */
    public function transform($order)
    {
        /** @var Order $order */
        return array_merge(parent::transform($order), [
            'order_number' => $order->getOrderNumber(),
        ]);
    }
}
