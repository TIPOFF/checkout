<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Models;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Authorization\Models\EmailAddress;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Checkout\Models\Order;
use Tipoff\Checkout\Services\Cart\Purchase;
use Tipoff\Checkout\Tests\Support\Models\TestSellable;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\Support\Contracts\Checkout\OrderInterface;

class CartModelTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();

        TestSellable::createTable();
    }

    /** @test */
    public function create()
    {
        $cart = Cart::factory()->create();
        $this->assertNotNull($cart);
    }

    /** @test */
    public function get_cart_items()
    {
        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        $cartItems = $cart->getItems();
        $this->assertCount(0, $cartItems);

        CartItem::factory()
            ->count(3)
            ->withSellable(TestSellable::factory()->create())
            ->create([
                'cart_id' => $cart,
            ]);

        $cart->refresh();

        $cartItems = $cart->getItems();
        $this->assertCount(3, $cartItems);
    }

    /** @test */
    public function is_owner_email_address_id()
    {
        $emailAddress = EmailAddress::factory()->create();

        $cart = Cart::factory()->create([
            'email_address_id' => $emailAddress,
        ]);

        $result = $cart->isOwnerEmailAddressId($emailAddress->id);

        $this->assertTrue($result);
    }

    /** @test */
    public function can_purchase()
    {
        /** @var Cart $cart */
        $cart = Cart::factory()->create();

        /** @var Order $order */
        $order = Order::factory()->create();

        $this->partialMock(Purchase::class, function($mock) use ($cart, $order) {
            $mock->shouldReceive('__invoke')
                    ->once()
                    ->with($cart, 'paymethod')
                    ->andReturn($order);
        });

        $order = $cart->purchase('paymethod');

        $this->assertInstanceOf(OrderInterface::class, $order);
    }
}
