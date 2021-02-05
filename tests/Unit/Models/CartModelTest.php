<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Models;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Tests\TestCase;

class CartModelTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function create()
    {
        $cart = Cart::factory()->create();
        $this->assertNotNull($cart);
    }

}
