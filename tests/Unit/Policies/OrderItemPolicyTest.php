<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Policies;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Authorization\Models\User;
use Tipoff\Checkout\Models\Order;
use Tipoff\Checkout\Models\OrderItem;
use Tipoff\Checkout\Tests\TestCase;

class OrderItemPolicyTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function view_any()
    {
        $user = self::createPermissionedUser('view order items', true);
        $this->assertTrue($user->can('viewAny', OrderItem::class));

        $user = self::createPermissionedUser('view order items', false);
        $this->assertTrue($user->can('viewAny', OrderItem::class));
    }

    /**
     * @test
     * @dataProvider data_provider_for_all_permissions_as_owner
     */
    public function all_permissions_as_owner(string $permission, bool $expected)
    {
        $user = User::factory()->create();
        $orderItem = OrderItem::factory()->make([
            'order_id' => Order::factory()->create([
                'user_id' => $user,
            ]),
        ]);

        $this->assertEquals($expected, $user->can($permission, $orderItem));
    }

    public function data_provider_for_all_permissions_as_owner()
    {
        return [
            'view' => [ 'view', true ],
            'create' => [ 'create', false ],
            'update' => [ 'update', false ],
            'delete' => [ 'delete', false ],
        ];
    }
}
