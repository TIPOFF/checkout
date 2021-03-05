<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\View\Components\Cart;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\Support\Objects\DiscountableValue;

class CartTotalTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function null_cart()
    {
        $view = $this->blade(
            '<x-tipoff-cart-total :cart="$cart" />',
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
            '<x-tipoff-cart-total :cart="$cart" />',
            ['cart' => $cart]
        );

        $view->assertSee('Taxes: $0.00');
        $view->assertSee('Fees: $0.00');
        $view->assertSee('Total: $0.00');
    }

    /** @test */
    public function cart_with_values()
    {
        $cart = \Mockery::mock(Cart::class)->makePartial();
        $cart->shouldReceive('getFeeTotal')
            ->once()
            ->andReturn(new DiscountableValue(234));
        $cart->tax = 123;
        $cart->item_amount_total = new DiscountableValue(3345);

        $view = $this->blade(
            '<x-tipoff-cart-total :cart="$cart" />',
            ['cart' => $cart]
        );

        $view->assertSee('Taxes: $1.23');
        $view->assertSee('Fees: $2.34');
        $view->assertSee('Total: $34.68');
    }
}
