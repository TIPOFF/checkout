<?php

declare(strict_types=1);

use Tipoff\Authorization\Permissions\BasePermissionsMigration;

class AddCheckoutPermissions extends BasePermissionsMigration
{
    public function up()
    {
        $permissions = [
            'view carts',
            'view orders',
            'view order items',
        ];
        $this->createPermissions($permissions);
    }
}
