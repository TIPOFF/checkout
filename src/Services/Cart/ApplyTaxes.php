<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Services\Cart;

use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Support\Contracts\Taxes\TaxRequest;

class ApplyTaxes
{
    public function __invoke(Cart $cart): Cart
    {
        if ($service = findService(TaxRequest::class)) {
            /** @var TaxRequest $service */
            $taxRequest = $service::createTaxRequest();

            $cart->cartItems->each(function (CartItem $cartItem) use ($taxRequest) {
                $taxRequest->createTaxRequestItem(
                    $cartItem->getId(),
                    $cartItem->getLocationId(),
                    $cartItem->getTaxCode(),
                    $cartItem->getAmountTotal()->getDiscountedAmount()
                );
            });

            $taxRequest->calculateTax();

            $cart->cartItems->each(function (CartItem $cartItem) use ($taxRequest) {
                $taxRequest = $taxRequest->getTaxRequestItem($cartItem->getId());
                $cartItem->setTax($taxRequest ? $taxRequest->getTax() : 0);
            });
        }

        return $cart;
    }
}
