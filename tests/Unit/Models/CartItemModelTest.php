<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Models;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tipoff\checkout\Models\CartItem;
use Tipoff\Checkout\Tests\TestCase;

class CartItemModelTest extends TestCase
{
    use DatabaseTransactions;
    use WithFaker;

    /** @test */
    public function create()
    {
        $model = CartItem::factory()->create();
        $this->assertNotNull($model);
    }
}
