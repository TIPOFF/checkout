<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Symfony\Component\HttpFoundation\Response;
use Tipoff\Authorization\Models\EmailAddress;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Checkout\Tests\Support\Models\TestSellable;
use Tipoff\Checkout\Tests\TestCase;

class CartItemControllerTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function index()
    {
        TestSellable::createTable();
        $sellable = TestSellable::factory()->create();

        // First user
        $emailAddress = EmailAddress::factory()->create();
        $cart = Cart::factory()->create([
            'email_address_id' => $emailAddress,
        ]);
        CartItem::factory()->withSellable($sellable)->count(3)->create([
            'cart_id' => $cart,
        ]);
        $cart->refresh()->save();

        // Second user
        $emailAddress = EmailAddress::factory()->create();
        $cart = Cart::factory()->create([
            'email_address_id' => $emailAddress,
        ]);
        CartItem::factory()->withSellable($sellable)->count(4)->create([
            'cart_id' => $cart,
        ]);
        $cart->refresh()->save();

        $this->actingAs($emailAddress, 'email');

        $response = $this->getJson($this->apiUrl('cart-items'))
            ->assertOk();

        $this->assertCount(4, $response->json('data'));
    }

    /** @test */
    public function show_cart_item_i_own()
    {
        TestSellable::createTable();
        $sellable = TestSellable::factory()->create();

        /** @var Cart $cart */
        $cart = Cart::factory()->create();
        CartItem::factory()->withSellable($sellable)->count(4)->create([
            'cart_id' => $cart,
        ]);
        $cart->refresh()->save();
        $cartItem = $cart->cartItems->first();

        $this->actingAs($cart->emailAddress, 'email');

        $response = $this->getJson($this->apiUrl("cart-items/{$cartItem->id}"))
            ->assertOk();

        $this->assertEquals($cartItem->id, $response->json('data.id'));
    }

    /** @test */
    public function show_cart_item_i_dont_own()
    {
        TestSellable::createTable();
        $sellable = TestSellable::factory()->create();

        /** @var Cart $cart */
        $cart = Cart::factory()->create();
        CartItem::factory()->withSellable($sellable)->count(4)->create([
            'cart_id' => $cart,
        ]);
        $cart->refresh()->save();
        $cartItem = $cart->cartItems->first();

        $this->actingAs(EmailAddress::factory()->create(), 'email');

        $this->getJson($this->apiUrl("cart-items/{$cartItem->id}"))
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /** @test */
    public function store_basic_cart_item()
    {
        TestSellable::createTable();
        $sellable = TestSellable::factory()->create();

        $emailAddress = EmailAddress::factory()->create();
        $this->actingAs($emailAddress, 'email');

        $response = $this->postJson($this->apiUrl('cart-items'), [
            'sellable_type' => get_class($sellable),
            'sellable_id' => $sellable->id,
            'item_id' => 'abc',
            'amount' => 1234,
        ])->assertOk();

        $this->assertEquals(1234, $response->json('data.amount_each'));

        $response = $this->getJson($this->apiUrl('cart?include=items'));
        $this->assertCount(1, $response->json('data.items.data'));
    }

    /** @test */
    public function store_extended_cart_item()
    {
        TestSellable::createTable();
        $sellable = TestSellable::factory()->create();

        $emailAddress = EmailAddress::factory()->create();
        $this->actingAs($emailAddress, 'email');

        $response = $this->postJson($this->apiUrl('cart-items'), [
            'sellable_type' => get_class($sellable),
            'sellable_id' => $sellable->id,
            'item_id' => 'abc',
            'amount' => 1234,
            'location_id' => 1,
            'tax_code' => 'ABC',
            'expires_at' => (string) Carbon::now()->addMinutes(10),
        ])->assertOk();

        $this->assertEquals(1, $response->json('data.location_id'));
        $this->assertEquals('ABC', $response->json('data.tax_code'));
        $this->assertNotNull($response->json('data.expires_at'));

        $response = $this->getJson($this->apiUrl('cart?include=items'));
        $this->assertCount(1, $response->json('data.items.data'));
    }

    /** @test */
    public function store_unknown_sellable_type()
    {
        $emailAddress = EmailAddress::factory()->create();
        $this->actingAs($emailAddress, 'email');

        $response = $this->postJson($this->apiUrl('cart-items'), [
            'sellable_type' => 'notaclass',
            'sellable_id' => 123,
            'item_id' => 'abc',
            'amount' => 1234,
        ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertEquals('Sellable type is not defined.', $response->json('errors.sellable_type.0'));
    }

    /** @test */
    public function store_not_a_sellable_type()
    {
        $emailAddress = EmailAddress::factory()->create();
        $this->actingAs($emailAddress, 'email');

        $response = $this->postJson($this->apiUrl('cart-items'), [
            'sellable_type' => Model::class,
            'sellable_id' => 123,
            'item_id' => 'abc',
            'amount' => 1234,
        ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertEquals('Type is not sellable.', $response->json('errors.sellable_type.0'));
    }

    /** @test */
    public function store_sellable_item_not_found()
    {
        TestSellable::createTable();

        $emailAddress = EmailAddress::factory()->create();
        $this->actingAs($emailAddress, 'email');

        $response = $this->postJson($this->apiUrl('cart-items'), [
            'sellable_type' => TestSellable::class,
            'sellable_id' => 123,
            'item_id' => 'abc',
            'amount' => 1234,
        ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertEquals('Sellable item not found.', $response->json('errors.sellable_id.0'));
    }

    /** @test */
    public function update_cart_item_i_own()
    {
        TestSellable::createTable();
        $sellable = TestSellable::factory()->create();

        /** @var Cart $cart */
        $cart = Cart::factory()->create();
        CartItem::factory()->withSellable($sellable)->count(4)->create([
            'cart_id' => $cart,
            'quantity' => 1,
        ]);
        $cart->refresh()->save();
        $cartItem = $cart->cartItems->first();

        $this->actingAs($cart->emailAddress, 'email');

        $response = $this
            ->putJson($this->apiUrl("cart-items/{$cartItem->id}"), [
                'quantity' => 2,
            ])
            ->assertOk();

        $this->assertEquals(2, $response->json('data.quantity'));
    }

    /** @test */
    public function update_cart_item_i_dont_own()
    {
        TestSellable::createTable();
        $sellable = TestSellable::factory()->create();

        /** @var Cart $cart */
        $cart = Cart::factory()->create();
        CartItem::factory()->withSellable($sellable)->count(4)->create([
            'cart_id' => $cart,
        ]);
        $cart->refresh()->save();
        $cartItem = $cart->cartItems->first();

        $this->actingAs(EmailAddress::factory()->create(), 'email');

        $this
            ->putJson($this->apiUrl("cart-items/{$cartItem->id}"), [
                'quantity' => 2,
            ])
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /** @test */
    public function delete_cart_item_i_own()
    {
        TestSellable::createTable();
        $sellable = TestSellable::factory()->create();

        /** @var Cart $cart */
        $cart = Cart::factory()->create();
        CartItem::factory()->withSellable($sellable)->count(4)->create([
            'cart_id' => $cart,
        ]);
        $cart->refresh()->save();
        $cartItem = $cart->cartItems->first();

        $this->actingAs($cart->emailAddress, 'email');

        $response = $this->deleteJson($this->apiUrl("cart-items/{$cartItem->id}"))
            ->assertOk();

        $this->assertEquals('success', $response->json('data.message'));

        $response = $this->getJson($this->apiUrl('cart-items'))
            ->assertOk();

        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function delete_cart_item_i_dont_own()
    {
        TestSellable::createTable();
        $sellable = TestSellable::factory()->create();

        /** @var Cart $cart */
        $cart = Cart::factory()->create();
        CartItem::factory()->withSellable($sellable)->count(4)->create([
            'cart_id' => $cart,
        ]);
        $cart->refresh()->save();
        $cartItem = $cart->cartItems->first();

        $this->actingAs(EmailAddress::factory()->create(), 'email');

        $this->deleteJson($this->apiUrl("cart-items/{$cartItem->id}"))
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /** @test */
    public function index_not_logged_in()
    {
        $this->getJson($this->apiUrl('cart-items'))
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /** @test */
    public function show_not_logged_in()
    {
        TestSellable::createTable();
        $sellable = TestSellable::factory()->create();

        /** @var Cart $cart */
        $cart = Cart::factory()->create();
        CartItem::factory()->withSellable($sellable)->count(4)->create([
            'cart_id' => $cart,
        ]);
        $cart->refresh()->save();
        $cartItem = $cart->cartItems->first();

        $this->getJson($this->apiUrl("cart-items/{$cartItem->id}"))
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /** @test */
    public function failed_delete_cart_item()
    {
        CartItem::deleting(function () {
            return false;
        });

        TestSellable::createTable();
        $sellable = TestSellable::factory()->create();

        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        CartItem::factory()->withSellable($sellable)->count(4)->create([
            'cart_id' => $cart,
        ]);

        $cart->refresh()->save();
        $cartItem = $cart->cartItems->first();
        $this->actingAs($cart->emailAddress, 'email');

        $response = $this->deleteJson($this->apiUrl("cart-items/{$cartItem->id}"));

        $this->assertEquals('Failed to delete.', $response->json('errors.0.code'));
    }
}
