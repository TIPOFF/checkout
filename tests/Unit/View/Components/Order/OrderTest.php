<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\View\Components\Order;

use Illuminate\Foundation\Testing\DatabaseTransactions;
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
}
