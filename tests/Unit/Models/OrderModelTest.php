<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Models;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Checkout\Models\Order;
use Tipoff\Checkout\Tests\TestCase;

class OrderModelTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function create()
    {
        $this->markTestSkipped('PENDING UPDATE FOR NEW orders / order_items');

        $cart = Order::factory()->create();
        $this->assertNotNull($cart);
    }
}
