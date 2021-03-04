<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Transformers;

use Tipoff\Checkout\Models\CartItem;
use Tipoff\Checkout\Models\OrderItem;
use Tipoff\Support\Contracts\Checkout\BaseItemInterface;
use Tipoff\Support\Transformers\BaseTransformer;

abstract class BaseItemTransformer extends BaseTransformer
{
    protected $defaultIncludes = [
        'sellable',
    ];

    protected $availableIncludes = [
        'sellable',
    ];

    /**
     * @param CartItem|OrderItem $item
     * @return array
     */
    public function transform($item)
    {
        return [
            'id' => $item->getId(),
            'item_id' => $item->getItemId(),
            'description' => $item->getDescription(),
            'quantity' => $item->getQuantity(),
            'amount_each_original' => $item->getAmountEach()->getOriginalAmount(),
            'amount_each_discounts' => $item->getAmountEach()->getDiscounts(),
            'amount_each' => $item->getAmountEach()->getDiscountedAmount(),
            'amount_total_original' => $item->getAmountTotal()->getOriginalAmount(),
            'amount_total_discounts' => $item->getAmountTotal()->getDiscounts(),
            'amount_total' => $item->getAmountTotal()->getDiscountedAmount(),
            'tax' => $item->getTax(),
            'tax_code' => $item->getTaxCode(),
            'location_id' => $item->getLocationId(),
            'meta_data' => $item->getMetaData(null),
        ];
    }

    /**
     * @param CartItem|OrderItem $item
     * @return \League\Fractal\Resource\Item
     */
    public function includeSellable(BaseItemInterface $item)
    {
        $sellable = $item->getSellable();

        return $this->item($sellable, $sellable->getTransformer() ?? new SellableTransformer());
    }
}
