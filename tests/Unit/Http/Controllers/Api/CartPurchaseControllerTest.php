<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Symfony\Component\HttpFoundation\Response;
use Tipoff\Authorization\Models\EmailAddress;
use Tipoff\Authorization\Models\User;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Checkout\Tests\Support\Models\TestSellable;
use Tipoff\Checkout\Tests\TestCase;

class CartPurchaseControllerTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function simple_purchase()
    {
        TestSellable::createTable();
        $sellable = TestSellable::factory()->create();

        $user = User::factory()->create();
        EmailAddress::factory()->create([
            'user_id' => $user,
        ]);
        $this->actingAs($user);

        $this->postJson($this->apiUrl('cart-items'), [
            'sellable_type' => get_class($sellable),
            'sellable_id' => $sellable->id,
            'item_id' => 'abc',
            'amount' => 1234,
        ])->assertOk();

        $response = $this->postJson($this->apiUrl('cart/purchase'))
            ->assertOk();

        $this->assertNotNull($response->json('data.order_number'));
    }

    /** @test */
    public function cannot_purchase_empty_cart()
    {
        $user = User::factory()->create();
        EmailAddress::factory()->create([
            'user_id' => $user,
        ]);

        $this->actingAs($user);

        $response = $this
            ->postJson($this->apiUrl('cart/purchase'))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertEquals('Cart is empty.', $response->json('errors.cart.0'));
    }

    /** @test */
    public function cannot_purchase_expired_item()
    {
        TestSellable::createTable();

        $user = User::factory()->create();
        EmailAddress::factory()->create([
            'user_id' => $user,
        ]);

        $this->actingAs($user);

        /** @var TestSellable $sellable */
        $sellable = TestSellable::factory()->create();
        $sellable->addToCart(1);

        $sellable = TestSellable::factory()->create();

        /** @var CartItem $item */
        $item = $sellable->addToCart(1);
        $item->expires_at = Carbon::now()->subDays(1);
        $item->save();

        $response = $this
            ->postJson($this->apiUrl('cart/purchase'))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertEquals('Cart is empty.', $response->json('errors.cart.0'));
    }
}
