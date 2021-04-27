<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Http\Controllers;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Symfony\Component\HttpFoundation\Response;
use Tipoff\Authorization\Models\EmailAddress;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Checkout\Tests\Support\Models\TestSellable;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\Support\Contracts\Checkout\CodedCartAdjustment;
use Tipoff\Support\Contracts\Checkout\Discounts\DiscountInterface;

class CartControllerTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function show_logged_in()
    {
        $this->actingAs(EmailAddress::factory()->create(), 'email');

        $this->get(route('checkout.cart-show'))
            ->assertOk();
    }

    public function add_code_to_cart()
    {
        $discount = \Mockery::mock(CodedCartAdjustment::class);
        $discount->shouldReceive('applyToCart')->once();

        $service = \Mockery::mock(DiscountInterface::class);
        $service->shouldReceive('findByCode')->twice()->andReturn($discount);
        $service->shouldReceive('calculateAdjustments')->once();
        $service->shouldReceive('getCodesForCart')->once()->andReturn(['abcd']);
        $this->app->instance(DiscountInterface::class, $service);

        $this->actingAs(EmailAddress::factory()->create());

        $this->post(route('checkout.cart-add-code'), [
            'code' => 'abcd',
        ])
            ->assertRedirect(route('checkout.cart-show'));
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

        $this->post(route('checkout.cart-delete-item'), [
            'id' => $cartItem->id,
        ])
            ->assertRedirect(route('checkout.cart-show'));
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

        $this->post(route('checkout.cart-delete-item'), [
            'id' => $cartItem->id,
        ])
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /** @test */
    public function show_not_logged_in()
    {
        $this->get(route('checkout.cart-show'))
            ->assertRedirect(route('authorization.email-login'));
    }

    /** @test */
    public function delete_not_logged_in()
    {
        $this->post(route('checkout.cart-delete-item'), [
            'id' => 123,
        ])
            ->assertRedirect(route('authorization.email-login'));
    }

    /** @test */
    public function add_code_not_logged_in()
    {
        $this->post(route('checkout.cart-add-code'), [
            'code' => 'ABCD',
        ])
            ->assertRedirect(route('authorization.email-login'));
    }
}
