<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Objects;

use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\Order;
use Tipoff\Support\Objects\DiscountableValue;

class ContainerPricingDetail
{
    private DiscountableValue $itemAmount;
    private DiscountableValue $shipping;
    private int $discounts;
    private int $credits;
    private int $tax;

    /**
     * @param Cart|Order $container
     */
    public function __construct($container)
    {
        $this->itemAmount = $container->getItemAmountTotal();
        $this->shipping = $container->getShipping();
        $this->discounts = $container->getDiscounts();
        $this->credits = $container->getCredits();
        $this->tax = $container->getTax();
    }

    public function getBalanceDue(): int
    {
        $balance = $this->itemAmount
            ->addDiscounts($this->discounts)
            ->add($this->shipping)
            ->add(new DiscountableValue($this->tax))
            ->addDiscounts($this->credits);

        return $balance->getDiscountedAmount();
    }

    public function isEqual(ContainerPricingDetail $other): bool
    {
        return $this->itemAmount->isEqual($other->itemAmount) &&
            $this->shipping->isEqual($other->shipping) &&
            ($this->discounts === $other->discounts) &&
            ($this->credits === $other->discounts) &&
            ($this->tax === $other->tax);
    }
}
