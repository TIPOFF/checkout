<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\View\Components;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Authorization\Models\User;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Tests\Support\Models\TestSellable;
use Tipoff\Checkout\Tests\TestCase;

class CartTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();

        TestSellable::createTable();
    }

    /** @test */
    public function null_cart()
    {
        $view = $this->blade(
            '<x-tipoff-cart :cart="$cart" />',
            ['cart' => null]
        );

        $this->assertEquals('', (string) $view);
    }

    /** @test */
    public function empty_cart()
    {
        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        $view = $this->blade(
            '<x-tipoff-cart :cart="$cart" />',
            ['cart' => $cart]
        );

        $view->assertSee('Cart is empty');
    }

    /** @test */
    public function cart_with_item()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        /** @var TestSellable $sellable */
        $sellable = TestSellable::factory()->create();
        $sellable->addToCart(2);

        $view = $this->blade(
            '<x-tipoff-cart :cart="$cart" />',
            ['cart' => Cart::activeCart($user->id)]
        );

        $view->assertSee('Test Sellable');
        $view->assertSee('2');
        $view->assertSee('$10.00');
        $view->assertSee('$0.00');
        $view->assertSee('$20.00');
    }
}
