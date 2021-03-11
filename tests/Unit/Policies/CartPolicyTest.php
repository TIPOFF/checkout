<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Policies;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\Support\Contracts\Models\UserInterface;

class CartPolicyTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function view_any()
    {
        $user = self::createPermissionedUser('view carts', true);
        $this->assertTrue($user->can('viewAny', Cart::class));

        $user = self::createPermissionedUser('view carts', false);
        $this->assertFalse($user->can('viewAny', Cart::class));
    }

    /**
     * @test
     * @dataProvider data_provider_for_all_permissions_as_creator
     */
    public function all_permissions_as_creator(string $permission, UserInterface $user, bool $expected)
    {
        $discount = Cart::factory()->make([
            'creator_id' => $user,
        ]);

        $this->assertEquals($expected, $user->can($permission, $discount));
    }

    public function data_provider_for_all_permissions_as_creator()
    {
        return [
            'view-true' => [ 'view', self::createPermissionedUser('view carts', true), true ],
            'view-false' => [ 'view', self::createPermissionedUser('view carts', false), false ],
            'create-true' => [ 'create', self::createPermissionedUser('create carts', true), true ],
            'create-false' => [ 'create', self::createPermissionedUser('create carts', false), true ],
            'update-true' => [ 'update', self::createPermissionedUser('update carts', true), false ],
            'update-false' => [ 'update', self::createPermissionedUser('update carts', false), false ],
            'delete-true' => [ 'delete', self::createPermissionedUser('delete carts', true), true ],
            'delete-false' => [ 'delete', self::createPermissionedUser('delete carts', false), true ],
        ];
    }

    /**
     * @test
     * @dataProvider data_provider_for_all_permissions_not_creator
     */
    public function all_permissions_not_creator(string $permission, UserInterface $user, bool $expected)
    {
        $discount = Cart::factory()->make();

        $this->assertEquals($expected, $user->can($permission, $discount));
    }

    public function data_provider_for_all_permissions_not_creator()
    {
        // Permissions are identical for creator or others
        return $this->data_provider_for_all_permissions_as_creator();
    }
}
