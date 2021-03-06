<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Models;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Checkout\Tests\Support\Models\TestSellable;
use Tipoff\Checkout\Tests\TestCase;

class CartItemModelTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function create()
    {
        TestSellable::createTable();
        $sellable = TestSellable::factory()->create();

        $model = CartItem::factory()->withSellable($sellable)->create();
        $this->assertNotNull($model);
    }

    /** @test */
    public function set_amount_each_updates_total()
    {
        TestSellable::createTable();
        $sellable = TestSellable::factory()->create();

        /** @var CartItem $cartItem */
        $cartItem = CartItem::factory()->withSellable($sellable)->create([
            'quantity' => 2,
            'amount_each' => 1000,
        ]);

        $this->assertEquals(2000, $cartItem->getAmountTotal()->getOriginalAmount());
        $this->assertEquals(0, $cartItem->getAmountTotal()->getDiscounts());
        $this->assertEquals(2000, $cartItem->getAmountTotal()->getDiscountedAmount());

        $cartItem->setAmountEach($cartItem->getAmountEach()->addDiscounts(250));

        $this->assertEquals(2000, $cartItem->getAmountTotal()->getOriginalAmount());
        $this->assertEquals(500, $cartItem->getAmountTotal()->getDiscounts());
        $this->assertEquals(1500, $cartItem->getAmountTotal()->getDiscountedAmount());
    }
}
