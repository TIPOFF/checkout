<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Http\Controllers;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Authorization\Models\EmailAddress;
use Tipoff\Authorization\Models\User;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Checkout\Tests\Support\Models\TestSellable;
use Tipoff\Checkout\Tests\TestCase;

class CheckoutControllerTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function show_logged_in()
    {
        $this->actingAs(EmailAddress::factory()->create(), 'email');

        $this->get(route('checkout.show'))
            ->assertOk();
    }

    /** @test */
    public function purchase()
    {
        TestSellable::createTable();
        $sellable = TestSellable::factory()->create();

        $user = User::factory()->create();

        $cart = Cart::factory()->create([
            'email_address_id' => EmailAddress::factory()->create([
                'user_id' => $user,
            ]),
        ]);

        CartItem::factory()->withSellable($sellable)->count(4)->create([
            'cart_id' => $cart,
        ]);

        $this->actingAs($user);

        $this->post(route('checkout.purchase'), [])
            ->assertRedirect(route('checkout.confirmation'));
    }

    /** @test */
    public function show_not_logged_in()
    {
        $this->get(route('checkout.show'))
            ->assertRedirect(route('authorization.email-login'));
    }

    /** @test */
    public function purchase_not_logged_in()
    {
        $this->post(route('checkout.purchase'))
            ->assertRedirect(route('authorization.email-login'));
    }

    /** @test */
    public function confirmation_not_logged_in()
    {
        $this->get(route('checkout.confirmation'))
            ->assertRedirect(route('authorization.login'));
    }
}
