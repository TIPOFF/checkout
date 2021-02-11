<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Policies;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Checkout\Models\Order;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\Support\Contracts\Models\UserInterface;

class OrderPolicyTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function view_any()
    {
        $user = self::createPermissionedUser('view orders', true);
        $this->assertTrue($user->can('viewAny', Order::class));

        $user = self::createPermissionedUser('view orders', false);
        $this->assertFalse($user->can('viewAny', Order::class));
    }

    /**
     * @test
     * @dataProvider data_provider_for_all_permissions_as_creator
     */
    public function all_permissions_as_creator(string $permission, UserInterface $user, bool $expected)
    {
        $discount = Order::factory()->make([
            'creator_id' => $user,
        ]);

        $this->assertEquals($expected, $user->can($permission, $discount));
    }

    public function data_provider_for_all_permissions_as_creator()
    {
        return [
            'view-true' => [ 'view', self::createPermissionedUser('view orders', true), true ],
            'view-false' => [ 'view', self::createPermissionedUser('view orders', false), false ],
            'create-true' => [ 'create', self::createPermissionedUser('create orders', true), false ],
            'create-false' => [ 'create', self::createPermissionedUser('create orders', false), false ],
            'update-true' => [ 'update', self::createPermissionedUser('update orders', true), false ],
            'update-false' => [ 'update', self::createPermissionedUser('update orders', false), false ],
            'delete-true' => [ 'delete', self::createPermissionedUser('delete orders', true), false ],
            'delete-false' => [ 'delete', self::createPermissionedUser('delete orders', false), false ],
        ];
    }

    /**
     * @test
     * @dataProvider data_provider_for_all_permissions_not_creator
     */
    public function all_permissions_not_creator(string $permission, UserInterface $user, bool $expected)
    {
        $discount = Order::factory()->make();

        $this->assertEquals($expected, $user->can($permission, $discount));
    }

    public function data_provider_for_all_permissions_not_creator()
    {
        // Permissions are identical for creator or others
        return $this->data_provider_for_all_permissions_as_creator();
    }
}
