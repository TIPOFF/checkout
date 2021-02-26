<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Support\Models;

use Tipoff\Support\Contracts\Checkout\CartInterface;
use Tipoff\Support\Contracts\Checkout\CartItemInterface;
use Tipoff\TestSupport\Models\TestSellable as BaseSellable;

class TestSellable extends BaseSellable
{
    /**
     * Simulated API handler for converting form submission parameters into a cart item
     */
    public function addToCart(int $quantity, $location = null): CartItemInterface
    {
        /** @var CartInterface $service */
        if ($service = findService(CartInterface::class)) {
            // Get active cart for authenticated user
            $user = auth()->user();
            $cart = $service::activeCart($user->id);

            // $amount is the total amount for the item - TODO / TBD - need to confirm if it should be price each
            $amount = ($quantity * 1000);

            // Create a DETACHED item, chaining additional settings that may exist, but aren't required
            $item = $service::createItem($this, $this->getItemId(), $amount, $quantity)
                ->setLocationId($location ? $location->getId() : null)
                ->setTaxCode('AB-12');

            // Add to cart
            return $cart->upsertItem($item);
        }

        throw new \Exception('Checkout is not enabled');
    }

    /**
     * Alternate simulated API handler that can handle both add and update
     */
    public function upsertToCart(int $quantity, $location = null)
    {
        /** @var CartInterface $service */
        if ($service = findService(CartInterface::class)) {
            // Get active cart for authenticated user
            $user = auth()->user();
            $cart = $service::activeCart($user->id);

            // $amount is the total amount for the item - TODO / TBD - need to confirm if it should be price each
            $amount = ($quantity * 1000);

            if ($item = $cart->findItem($this, $this->getItemId())) {
                // Update core fields of existing item
                $item->setAmount($amount)->setQuantity($quantity);
            } else {
                // Create new item with core values
                $item = $service::createItem($this, $this->getItemId(), $amount, $quantity);
            }

            // Set common fields for optional and upsert to cart
            $item
                ->setLocationId($location ? $location->getId() : null)
                ->setTaxCode('AB-12');

            return $cart->upsertItem($item);
        }

        throw new \Exception('Checkout is not enabled');
    }

    /**
     * Simulated API handler that adds a linked fee item to a cart
     */
    public function upsertToCartWithFee(int $quantity, int $fee)
    {
        /** @var CartInterface $service */
        if ($service = findService(CartInterface::class)) {
            // Get active cart for authenticated user
            $user = auth()->user();
            $cart = $service::activeCart($user->id);

            $item = $this->upsertToCart($quantity);

            // If the same Sellable type is used, then the itemId MUST be unique
            $fee = $service::createItem($this, $this->getItemId() . '-fee', $fee)
                ->setParentItem($item);

            return $cart->upsertItem($fee);
        }

        throw new \Exception('Checkout is not enabled');
    }
}
