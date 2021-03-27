<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Http\Controllers\Api;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Authorization\Models\EmailAddress;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Checkout\Tests\Support\Models\TestSellable;
use Tipoff\Checkout\Tests\TestCase;

class CartControllerTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function index_with_no_cart()
    {
        $emailAddress = EmailAddress::factory()->create();

        $this->actingAs($emailAddress, 'email');

        $response = $this->getJson($this->apiUrl('cart'))
            ->assertOk();

        $this->assertNotNull($response->json('data.item_amount_total'));
        $this->assertEquals(0, $response->json('data.item_amount_total'));
    }

    /** @test */
    public function index_with_cart()
    {
        TestSellable::createTable();
        $sellable = TestSellable::factory()->create();

        /** @var Cart $cart */
        $cart = Cart::factory()->create();
        CartItem::factory()->withSellable($sellable)->count(4)->create([
            'cart_id' => $cart,
        ]);
        $cart->refresh()->save();

        $this->actingAs($cart->emailAddress, 'email');

        $prefix = config('tipoff.api.uri_prefix');
        $response = $this->getJson($this->apiUrl('cart'))
            ->assertOk();

        $this->assertNotNull($response->json('data.item_amount_total'));
        $this->assertGreaterThan(0, $response->json('data.item_amount_total'));
        $this->assertNull($response->json('data.items'));
    }

    /** @test */
    public function index_include_items()
    {
        TestSellable::createTable();
        $sellable = TestSellable::factory()->create();

        /** @var Cart $cart */
        $cart = Cart::factory()->create();
        CartItem::factory()->withSellable($sellable)->count(4)->create([
            'cart_id' => $cart,
        ]);
        $cart->refresh()->save();

        $this->actingAs($cart->emailAddress, 'email');

        $response = $this->getJson($this->apiUrl('cart?include=items'))
            ->assertOk();

        $this->assertNotNull($response->json('data.item_amount_total'));
        $this->assertGreaterThan(0, $response->json('data.item_amount_total'));
        $this->assertNotNull($response->json('data.items'));
        $this->assertCount(4, $response->json('data.items.data'));
        $this->assertNotNull($response->json('data.items.data.0.sellable.data'));
    }

    /** @test */
    public function delete_json()
    {
        $emailAddress = EmailAddress::factory()->create();
        /** @var Cart $cart */
        $cart = Cart::activeCart($emailAddress->id);

        $this->actingAs($emailAddress, 'email');

        $response = $this->deleteJson($this->apiUrl('cart'))
            ->assertOk();

        $this->assertEquals('success', $response->json('data.message'));

        $cart->refresh();
        $this->assertNotNull($cart->deleted_at);
    }

    /** @test */
    public function index_not_logged_in()
    {
        $response = $this->getJson($this->apiUrl('cart'))
            ->assertOk();

        $this->assertCount(0, $response->json('data'));
    }

    /** @test */
    public function delete_not_logged_in()
    {
        $response = $this->deleteJson($this->apiUrl('cart'))
            ->assertOk();

        $this->assertEquals('success', $response->json('data.message'));
    }
}
