<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\View\Components\Cart;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Component;
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

        $this->artisan('view:clear')->run();

        TestSellable::createTable();

        // Dynamic Component has static data, so need to ensure this gets included
        $this->resetDynamicComponent();
        Blade::component('tipoff-custom-cart-item', CustomItem::class);
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

    /** @test */
    public function cart_with_custom_item()
    {
        CustomSellable::createTable();

        $user = User::factory()->create();

        $this->actingAs($user);

        /** @var CustomSellable $sellable */
        $sellable = CustomSellable::factory()->create();
        $sellable->addToCart(2);

        $view = $this->blade(
            '<x-tipoff-cart :cart="$cart" />',
            ['cart' => Cart::activeCart($user->id)]
        );

        $view->assertSee('I am custom!');
    }
}

class CustomSellable extends TestSellable
{
    public function getViewComponent($context = null)
    {
        return implode('-', ['tipoff','custom', $context]);
    }
}

class CustomItem extends Component
{
    public function render()
    {
        return '<div>I am custom!</div>';
    }
}
