<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Policies;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Checkout\Tests\TestCase;
use Tipoff\Support\Contracts\Authorization\UserInterface;

class CartItemPolicyTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function view_any()
    {
        $user = self::createPermissionedUser('view cart items', true);
        $this->assertTrue($user->can('viewAny', CartItem::class));

        $user = self::createPermissionedUser('view cart items', false);
        $this->assertTrue($user->can('viewAny', CartItem::class));
    }

    /**
     * @test
     * @dataProvider data_provider_for_all_permissions_as_creator
     */
    public function all_permissions_as_creator(string $permission, UserInterface $user, bool $expected)
    {
        $discount = CartItem::factory()->make([
            'creator_id' => $user,
        ]);

        $this->assertEquals($expected, $user->can($permission, $discount));
    }

    public function data_provider_for_all_permissions_as_creator()
    {
        return [
            'view-true' => [ 'view', self::createPermissionedUser('view cart items', true), true ],
            'view-false' => [ 'view', self::createPermissionedUser('view cart items', false), true ],
            'create-true' => [ 'create', self::createPermissionedUser('create cart items', true), true ],
            'create-false' => [ 'create', self::createPermissionedUser('create cart items', false), true ],
            'update-true' => [ 'update', self::createPermissionedUser('update cart items', true), true ],
            'update-false' => [ 'update', self::createPermissionedUser('update cart items', false), true ],
            'delete-true' => [ 'delete', self::createPermissionedUser('delete cart items', true), true ],
            'delete-false' => [ 'delete', self::createPermissionedUser('delete cart items', false), true ],
        ];
    }

    /**
     * @test
     * @dataProvider data_provider_for_all_permissions_not_creator
     */
    public function all_permissions_not_creator(string $permission, UserInterface $user, bool $expected)
    {
        $discount = CartItem::factory()->make();

        $this->assertEquals($expected, $user->can($permission, $discount));
    }

    public function data_provider_for_all_permissions_not_creator()
    {
        // Permissions are identical for creator or others
        return $this->data_provider_for_all_permissions_as_creator();
    }
}
