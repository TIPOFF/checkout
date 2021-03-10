<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Transformers;

use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use Tipoff\Addresses\Models\Address;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\Order;
use Tipoff\Support\Transformers\BaseTransformer;

abstract class BaseItemContainerTransformer extends BaseTransformer
{
    protected $defaultIncludes = [
    ];

    protected $availableIncludes = [
        'items',
        'billing_address',
        'shipping_address',
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
            'shipping_original' => $container->getShipping()->getOriginalAmount(),
            'shipping_discounts' => $container->getShipping()->getDiscounts(),
            'shipping' => $container->getShipping()->getDiscountedAmount(),
            'item_amount_total_original' => $container->getItemAmountTotal()->getOriginalAmount(),
            'item_amount_total_discounts' => $container->getItemAmountTotal()->getDiscounts(),
            'item_amount_total' => $container->getItemAmountTotal()->getDiscountedAmount(),
            'discounts' => $container->getDiscounts(),
            'credits' => $container->getCredits(),
            'codes' => $container->getCodes(),
            'tax' => $container->getTax(),
            'user_id' => $container->getUser()->getId(),
            'location_id' => $container->getLocationId(),
            'creator_id' => $container->creator_id,
            'updater_id' => $container->updater_id,
            'created_at' => (string) $container->created_at,
            'updated_at' => (string) $container->updated_at,
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

    /**
     * @param Cart|Order $container
     * @return Item|null
     */
    public function includeShippingAddress($container)
    {
        return $this->transformAddress($container->getShippingAddress());
    }

    /**
     * @param Cart|Order $container
     * @return Item|null
     */
    public function includeBillingAddress($container)
    {
        return $this->transformAddress($container->getBillingAddress());
    }

    private function transformAddress(?Address $address)
    {
        $transformer = $address ? $address->getTransformer() : null;

        return $transformer ? $this->item($address, $transformer) : null;
    }
}
