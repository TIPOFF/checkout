<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Http\Controllers;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Symfony\Component\HttpFoundation\Response;
use Tipoff\Authorization\Models\EmailAddress;
use Tipoff\Authorization\Models\User;
use Tipoff\Checkout\Models\Order;
use Tipoff\Checkout\Models\OrderItem;
use Tipoff\Checkout\Tests\Support\Models\TestSellable;
use Tipoff\Checkout\Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function index()
    {
        // First user
        $user = User::factory()->create();
        Order::factory()->count(4)->create([
            'user_id' => $user,
        ]);

        // Second user
        $user = User::factory()->create();
        Order::factory()->count(4)->create([
            'user_id' => $user,
        ]);

        $this->actingAs($user);

        $this->get($this->webUrl('orders'))
            ->assertOk()
            ->assertSee("-- O:4 --");
    }

    /** @test */
    public function show_order_i_own()
    {
        TestSellable::createTable();
        $sellable = TestSellable::factory()->create();

        /** @var Order $order */
        $order = Order::factory()->create();
        OrderItem::factory()->withSellable($sellable)->count(4)->create([
            'order_id' => $order,
        ]);
        $order->refresh()->save();

        $this->actingAs($order->getUser());

        $this->get($this->webUrl("orders/{$order->id}"))
            ->assertOk()
            ->assertSee("-- O:{$order->order_number} --");
    }

    /** @test */
    public function show_order_i_dont_own()
    {
        /** @var Order $order */
        $order = Order::factory()->create();

        $this->actingAs(User::factory()->create());

        $this->get($this->webUrl("orders/{$order->id}"))
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /** @test */
    public function index_not_logged_in()
    {
        $this->get($this->webUrl('orders'))
            ->assertRedirect('login');

        $this->actingAs(EmailAddress::factory()->create(), 'email');

        $this->get($this->webUrl('orders'))
            ->assertRedirect('login');
    }

    /** @test */
    public function show_not_logged_in()
    {
        /** @var Order $order */
        $order = Order::factory()->create();

        $this->get($this->webUrl("orders/{$order->id}"))
            ->assertRedirect('login');

        $this->actingAs(EmailAddress::factory()->create(), 'email');

        $this->get($this->webUrl("orders/{$order->id}"))
            ->assertRedirect('login');
    }
}
