<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Objects;

use Tipoff\Checkout\Models\Cart;
use Tipoff\Support\Objects\DiscountableValue;

class CartPricingDetail
{
    private DiscountableValue $itemAmount;
    private DiscountableValue $shipping;
    private int $discounts;
    private int $credits;
    private int $tax;

    public function __construct(Cart $cart)
    {
        $this->itemAmount = $cart->getItemAmount();
        $this->shipping = $cart->getShipping();
        $this->discounts = $cart->discounts ?? 0;
        $this->credits = $cart->credits ?? 0;
        $this->tax = $cart->tax ?? 0;
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

    public function isEqual(CartPricingDetail $other): bool
    {
        return $this->itemAmount->isEqual($other->itemAmount) &&
            $this->shipping->isEqual($other->shipping) &&
            ($this->discounts === $other->discounts) &&
            ($this->credits === $other->discounts) &&
            ($this->tax === $other->tax);
    }
}
