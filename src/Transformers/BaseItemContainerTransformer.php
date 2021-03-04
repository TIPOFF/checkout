<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Transformers;

use League\Fractal\Resource\Collection;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\Order;
use Tipoff\Support\Transformers\BaseTransformer;

abstract class BaseItemContainerTransformer extends BaseTransformer
{
    protected $defaultIncludes = [
    ];

    protected $availableIncludes = [
        'items',
    ];

    abstract public function getItemTransformer(): BaseTransformer;

    /**
     * @param Cart|Order $container
     * @return array
     */
    public function transform($container)
    {
        return [
            'id' => $container->getId(),
            'shipping' => $container->getShipping()->getDiscountedAmount(),
            'item_amount' => $container->getItemAmountTotal()->getDiscountedAmount(),
            'discounts' => $container->getDiscounts(),
            'credits' => $container->getCredits(),
            'codes' => $container->getCodes(),
            'tax' => $container->getTax(),
            'user_id' => $container->getUser()->getId(),
            'expires_at' => $container->getExpiresAt(),
            'location_id' => $container->getLocationId(),
        ];
    }

    /**
     * @param Cart|Order $container
     * @return Collection
     */
    public function includeItems($container)
    {
        return $this->collection($container->getItems(), $this->getItemTransformer());
    }
}
