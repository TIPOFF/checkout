<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Http\Controllers\Api;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\Authorization\Models\User;

class CartControllerTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function index()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->getJson('tipoff/cart')
            ->assertOk();

        $this->assertEquals(0, $response->json('data.amount'));
    }
}
