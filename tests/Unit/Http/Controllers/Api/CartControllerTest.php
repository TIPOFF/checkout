<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Http\Controllers\Api;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\TestSupport\Models\User;

class CartControllerTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function index()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->getJson('tipoff/cart')
            ->assertOk();

        $this->assertEquals(0, $response->json('data.amount'));
    }

    /** @test */
    public function delete_json()
    {
        $user = User::factory()->create();
        /** @var Cart $cart */
        $cart = Cart::activeCart($user->id);

        $this->actingAs($user);

        $response = $this->deleteJson('tipoff/cart')
            ->assertOk();

        $this->assertEquals('success', $response->json('data.message'));

        $cart->refresh();
        $this->assertNotNull($cart->deleted_at);
    }

    /** @test */
    public function index_not_logged_in()
    {
        $response = $this->getJson('tipoff/cart')
            ->assertOk();

        $this->assertCount(0, $response->json('data'));
    }

    /** @test */
    public function delete_not_logged_in()
    {
        $this->logToStderr();
        $response = $this->deleteJson('tipoff/cart')
            ->assertOk();

        $this->assertEquals('success', $response->json('data.message'));
    }
}
