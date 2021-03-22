<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Models;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Tests\TestCase;

class CheckoutModelTest extends TestCase
{
    use DatabaseTransactions;
    use WithFaker;

    /** @test */
    public function create()
    {
        $model = Cart::factory()->create();
        $this->assertNotNull($model);
    }
}
