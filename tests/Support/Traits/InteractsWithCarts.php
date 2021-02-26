<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Support\Traits;

use Tipoff\Checkout\Models\Cart;
use Tipoff\Support\Contracts\Sellable\Sellable;
use Tipoff\TestSupport\Models\TestSellable;

trait InteractsWithCarts
{
    protected string $sellableClass;
    protected Cart $cart;
    protected Sellable $sellable;

    protected function setupCart(string $sellableClass = TestSellable::class)
    {
        $this->sellableClass = $sellableClass;
        $sellableClass::createTable();

        $this->sellable = $this->createSellable();
        $this->cart = Cart::factory()->create();
    }

    protected function addCartItems(array $items): Cart
    {
        foreach ($items as $idx => $item) {
            [$amount, $quantity] = $item;

            $this->cart->upsertItem(
                Cart::createItem($this->sellable, "item-{$idx}", $amount, $quantity)
            );
        }

        return $this->cart;
    }

    protected function withCart(array $items, \Closure $closure)
    {
        $result = ($closure)($this->addCartItems($items));

        // Save results so we can inspect
        $this->cart->cartItems->each->save();
        $this->cart->save();

        return $result;
    }

    protected function createSellable(): Sellable
    {
        $class = $this->sellableClass;

        return $class::factory()->create();
    }
}
