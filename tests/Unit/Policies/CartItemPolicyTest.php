<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Policies;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tipoff\Authorization\Models\EmailAddress;
use Tipoff\Authorization\Models\User;
use Tipoff\Checkout\Models\Cart;
use Tipoff\Checkout\Models\CartItem;
use Tipoff\Checkout\Tests\TestCase;

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
     * @dataProvider data_provider_for_all_permissions_as_owner
     */
    public function all_permissions_as_owner(string $permission, bool $expected)
    {
        $user = User::factory()->create();
        $emailAddress = EmailAddress::factory()->create([
            'user_id' => $user,
        ]);
        $cartItem = CartItem::factory()->make([
            'cart_id' => Cart::factory()->create([
                'email_address_id' => $emailAddress,
            ]),
        ]);

        $this->assertEquals($expected, $user->can($permission, $cartItem));
    }

    public function data_provider_for_all_permissions_as_owner()
    {
        return [
            'view' => [ 'view', false ],
            'create' => [ 'create', true ],
            'update' => [ 'update', false ],
            'delete' => [ 'delete', false ],
        ];
    }

    /**
     * @test
     * @dataProvider data_provider_for_all_permissions_not_owner
     */
    public function all_permissions_not_owner(string $permission, bool $expected)
    {
        $cartItem = CartItem::factory()->make();
        $user = User::factory()->create();

        $this->assertEquals($expected, $user->can($permission, $cartItem));
    }

    public function data_provider_for_all_permissions_not_owner()
    {
        return [
            'view' => [ 'view', false ],
            'create' => [ 'create', true ],
            'update' => [ 'update', false ],
            'delete' => [ 'delete', false ],
        ];
    }
}
