<?php

declare(strict_types=1);

namespace Tipoff\Checkout\Tests\Unit\Policies;

use Illuminate\Foundation\Testing\DatabaseTransactions;
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
}
