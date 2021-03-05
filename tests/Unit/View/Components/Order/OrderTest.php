<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\View\Components\Order;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Component;
use Tipoff\Authorization\Models\User;
use Tipoff\Checkout\Models\Order;
use Tipoff\Checkout\Models\OrderItem;
use Tipoff\Checkout\Tests\Support\Models\TestSellable;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\Support\Objects\DiscountableValue;

class OrderTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();

        $this->artisan('view:clear')->run();

        TestSellable::createTable();

        // Dynamic Component has static data, so need to ensure this gets included
        $this->resetDynamicComponent();
        Blade::component('tipoff-custom-order-item', CustomItem::class);
    }

    /** @test */
    public function order_with_item()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        /** @var TestSellable $sellable */
        $sellable = TestSellable::factory()->create();
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->withSellable($sellable)->create([
            'quantity' => 2,
            'order_id' => $order,
            'amount_each' => new DiscountableValue(1000),
        ]);
        $order->refresh();

        $view = $this->blade(
            '<x-tipoff-order :order="$order" />',
            ['order' => $order]
        );

        $view->assertSee($orderItem->description);
        $view->assertSee('2');
        $view->assertSee('$10.00');
        $view->assertSee('$0.00');
        $view->assertSee('$20.00');
    }

    /** @test */
    public function order_with_custom_item()
    {
        CustomSellable::createTable();

        $user = User::factory()->create();

        $this->actingAs($user);

        /** @var CustomSellable $sellable */
        $sellable = CustomSellable::factory()->create();
        $order = Order::factory()->create();
        OrderItem::factory()->withSellable($sellable)->create([
            'order_id' => $order,
        ]);
        $order->refresh();

        $view = $this->blade(
            '<x-tipoff-order :order="$order" />',
            ['order' => $order]
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
