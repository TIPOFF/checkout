<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Objects;

use Tipoff\Checkout\Models\Cart;
use Tipoff\Support\Objects\DiscountableValue;

class CartPricingDetail
{
    private DiscountableValue $itemAmount;
    private DiscountableValue $shipping;
    private int $cartDiscounts;
    private int $cartCredits;
    private int $tax;

    public function __construct(Cart $cart)
    {
        $this->itemAmount = $cart->getItemAmount();
        $this->shipping = $cart->getShipping();
        $this->cartDiscounts = $cart->cart_discounts ?? 0;
        $this->cartCredits = $cart->cart_credits ?? 0;
        $this->tax = $cart->tax ?? 0;
    }

    public function getBalanceDue(): int
    {
        $balance = $this->itemAmount
            ->addDiscounts($this->cartDiscounts)
            ->add($this->shipping)
            ->add(new DiscountableValue($this->tax))
            ->addDiscounts($this->cartCredits);

        return $balance->getDiscountedAmount();
    }

    public function isEqual(CartPricingDetail $other): bool
    {
        return $this->itemAmount->isEqual($other->itemAmount) &&
            $this->shipping->isEqual($other->shipping) &&
            ($this->cartDiscounts === $other->cartDiscounts) &&
            ($this->cartCredits === $other->cartDiscounts) &&
            ($this->tax === $other->tax);
    }
}
