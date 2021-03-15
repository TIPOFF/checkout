<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Services\Cart;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Tipoff\Authorization\Models\User;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Services\Cart\Purchase;
use Tipoff\Checkout\Tests\Support\Models\TestSellable;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\Support\Contracts\Payment\PaymentInterface;
use Tipoff\Support\Events\Checkout\OrderCreated;
use Tipoff\Support\Events\Checkout\OrderItemCreated;

class PurchaseTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();

        TestSellable::createTable();

        $service = \Mockery::mock(PaymentInterface::class);
        $service->shouldReceive('createPayment', 'attachOrder')
            ->andReturnSelf();
        $this->app->instance(PaymentInterface::class, $service);
    }

    /** @test */
    public function purchase_single_item_cart()
    {
        Event::fake([
            OrderItemCreated::class,
            OrderCreated::class,
        ]);

        $user = User::factory()->create();

        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        $sellable = TestSellable::factory()->create();

        $cart->upsertItem(
            Cart::createItem($sellable, 'item-id', 1234, 2)
                ->setLocationId(123)
        );

        $this->actingAs($user);

        $handler = $this->app->make(Purchase::class);
        $order = ($handler)($cart, 'paymethod');

        $cart->refresh();
        $this->assertNotNull($cart->deleted_at);
        $this->assertEquals($cart->order_id, $order->id);

        Event::assertDispatched(OrderItemCreated::class, 1);
        Event::assertDispatched(OrderCreated::class, 1);
    }
}
