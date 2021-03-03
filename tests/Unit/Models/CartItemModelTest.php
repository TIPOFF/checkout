<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Models;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
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
}
