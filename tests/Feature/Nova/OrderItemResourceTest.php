<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Feature\Nova;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Authorization\Models\User;
use Tipoff\Checkout\Models\OrderItem;
use Tipoff\Checkout\Tests\Support\Models\TestSellable;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\Locations\Models\Location;

class OrderItemResourceTest extends TestCase
{
    use DatabaseTransactions;

    private const NOVA_ROUTE = 'nova-api/order-items';

    /**
     * @dataProvider dataProviderForIndexRoleLocationFilter
     * @test
     */
    public function index_role_location_filter(string $role, bool $isRoleLocationFiltered)
    {
        TestSellable::createTable();
        $sellable = TestSellable::factory()->create();

        $location1 = Location::factory()->create();
        $location2 = Location::factory()->create();

        OrderItem::factory()->count(2)->withSellable($sellable)->create([
            'location_id' => $location1,
        ]);

        OrderItem::factory()->count(3)->withSellable($sellable)->create([
            'location_id' => $location2,
        ]);

        /** @var User $user */
        $user = User::factory()->create();
        if ($role) {
            $user->assignRole($role);
        }
        $user->locations()->attach($location1);
        $this->actingAs($user);

        $response = $this->getJson(self::NOVA_ROUTE)
            ->assertOk();

        $this->assertCount($isRoleLocationFiltered ? 2 : 5, $response->json('resources'));
    }

    public function dataProviderForIndexRoleLocationFilter()
    {
        return [
            'Admin' => ['Admin', false],
            'Owner' => ['Owner', false],
            'Executive' => ['Executive', false],
            'Staff' => ['Staff', true],
        ];
    }

    /**
     * @dataProvider dataProviderForIndexByRole
     * @test
     */
    public function index_by_role(?string $role, bool $hasAccess, bool $canIndex)
    {
        TestSellable::createTable();
        $sellable = TestSellable::factory()->create();

        OrderItem::factory()->count(4)->withSellable($sellable)->create();

        $user = User::factory()->create();
        if ($role) {
            $user->assignRole($role);
        }
        $this->actingAs($user);

        $response = $this->getJson(self::NOVA_ROUTE)
            ->assertStatus($hasAccess ? 200 : 403);

        if ($hasAccess) {
            $this->assertCount($canIndex ? 4 : 0, $response->json('resources'));
        }
    }

    public function dataProviderForIndexByRole()
    {
        return [
            'Admin' => ['Admin', true, true],
            'Owner' => ['Owner', true, true],
            'Executive' => ['Executive', true, true],
            'Staff' => ['Staff', true, false],
            'Former Staff' => ['Former Staff', true, false],
            'Customer' => ['Customer', true, false],
            'No Role' => [null, true, false],
        ];
    }

    /**
     * @dataProvider dataProviderForShowByRole
     * @test
     */
    public function show_by_role(?string $role, bool $hasAccess, bool $canView)
    {
        TestSellable::createTable();
        $sellable = TestSellable::factory()->create();

        $model = OrderItem::factory()->withSellable($sellable)->create();

        $user = User::factory()->create();
        if ($role) {
            $user->assignRole($role);
        }
        $this->actingAs($user);

        $response = $this->getJson(self::NOVA_ROUTE . "/{$model->id}")
            ->assertStatus($hasAccess ? 200 : 403);

        if ($hasAccess && $canView) {
            $this->assertEquals($model->id, $response->json('resource.id.value'));
        }
    }

    public function dataProviderForShowByRole()
    {
        return [
            'Admin' => ['Admin', true, true],
            'Owner' => ['Owner', true, true],
            'Executive' => ['Executive', true, true],
            'Former Staff' => ['Former Staff', false, false],
            'Customer' => ['Customer', false, false],
            'No Role' => [null, false, false],
        ];
    }

    /**
     * @dataProvider dataProviderForDeleteByRole
     * @test
     */
    public function delete_by_role(?string $role, bool $hasAccess, bool $canDelete)
    {
        TestSellable::createTable();
        $sellable = TestSellable::factory()->create();

        $model = OrderItem::factory()->withSellable($sellable)->create();

        $user = User::factory()->create();
        if ($role) {
            $user->assignRole($role);
        }
        $this->actingAs($user);

        // Request never fails
        $this->deleteJson(self::NOVA_ROUTE . "?resources[]={$model->id}")
            ->assertStatus($hasAccess ? 200 : 403);

        // But deletion will only occur if user has permissions
        $this->assertDatabaseCount('order_items', $canDelete ? 0 : 1);
    }

    public function dataProviderForDeleteByRole()
    {
        return [
            'Admin' => ['Admin', true, false],
            'Owner' => ['Owner', true, false],
            'Executive' => ['Executive', true, false],
            'Staff' => ['Staff', true, false],
            'Former Staff' => ['Former Staff', true, false],
            'Customer' => ['Customer', true, false],
            'No Role' => [null, true, false],
        ];
    }
}
