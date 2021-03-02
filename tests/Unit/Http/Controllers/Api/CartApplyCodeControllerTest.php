<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Http\Controllers\Api;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\Support\Contracts\Checkout\CodedCartAdjustment;
use Tipoff\Support\Contracts\Checkout\Discounts\DiscountInterface;
use Tipoff\Authorization\Models\User;

class CartApplyCodeControllerTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function apply_bad_code()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this
            ->postJson('tipoff/cart/apply-code', [
                'code' => 'abcd',
            ])
            ->assertStatus(422);

        $this->assertEquals('Code not found', $response->json('errors.code.0'));
    }

    /** @test */
    public function apply_good_code()
    {
        $discounts = \Mockery::mock(DiscountInterface::class);
        $discounts->shouldReceive('findByCode')->twice()->andReturn(\Mockery::mock(CodedCartAdjustment::class));
        $discounts->shouldReceive('applyToCart')->once();
        $discounts->shouldReceive('calculateAdjustments')->once();
        $discounts->shouldReceive('getCodesForCart')->once()->andReturn(['abcd']);
        $this->app->instance(DiscountInterface::class, $discounts);

        $user = User::factory()->create();

        $this->actingAs($user);

        $this
            ->postJson('tipoff/cart/apply-code', [
                'code' => 'abcd',
            ])
            ->assertStatus(200);
    }
}
