<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Http\Controllers\Api;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Authorization\Models\EmailAddress;
use Tipoff\Authorization\Models\User;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\Support\Contracts\Checkout\CodedCartAdjustment;
use Tipoff\Support\Contracts\Checkout\Discounts\DiscountInterface;

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
        $discount = \Mockery::mock(CodedCartAdjustment::class);
        $discount->shouldReceive('applyToCart')->once();

        $service = \Mockery::mock(DiscountInterface::class);
        $service->shouldReceive('findByCode')->twice()->andReturn($discount);
        $service->shouldReceive('calculateAdjustments')->once();
        $service->shouldReceive('getCodesForCart')->once()->andReturn(['abcd']);
        $this->app->instance(DiscountInterface::class, $service);

        $user = User::factory()->create();
        EmailAddress::factory()->create([
            'user_id' => $user,
        ]);

        $this->actingAs($user);

        $this
            ->postJson('tipoff/cart/apply-code', [
                'code' => 'abcd',
            ])
            ->assertStatus(200);
    }
}
