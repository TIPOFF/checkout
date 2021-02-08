<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Models;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Checkout\Tests\TestCase;

class CartItemModelTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function create()
    {
        $cart = CartItem::factory()->create();
        $this->assertNotNull($cart);
    }
}
