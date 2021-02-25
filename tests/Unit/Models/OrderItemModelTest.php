<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Models;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Checkout\Models\OrderItem;
use Tipoff\Checkout\Tests\Support\Models\TestSellable;
use Tipoff\Checkout\Tests\TestCase;

class OrderItemModelTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function create()
    {
        TestSellable::createTable();
        $sellable = TestSellable::factory()->create();

        $model = OrderItem::factory()->withSellable($sellable)->create();
        $this->assertNotNull($model);
    }
}
