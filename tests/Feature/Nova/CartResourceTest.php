<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Feature\Nova;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Tests\TestCase;

class CartResourceTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function index()
    {
        Cart::factory()->count(4)->create();

        $this->actingAs(self::createPermissionedUser('view carts', true));

        $response = $this->getJson('nova-api/carts')
            ->assertOk();

        $this->assertCount(4, $response->json('resources'));
    }

    /** @test */
    public function show()
    {
        $cart = Cart::factory()->create();

        $this->actingAs(self::createPermissionedUser('view carts', true));

        $response = $this->getJson("nova-api/carts/{$cart->id}")
            ->assertOk();

        $this->assertEquals($cart->id, $response->json('resource.id.value'));
    }
}
