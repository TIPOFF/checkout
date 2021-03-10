<?php

declare(strict_types=1);

use Tipoff\Authorization\Permissions\BasePermissionsMigration;

class AddCheckoutPermissions extends BasePermissionsMigration
{
    public function up()
    {
        $permissions = [
            'view orders' => ['Owner', 'Staff'],
            'view order items' => ['Owner', 'Staff'],
        ];
        $this->createPermissions($permissions);
    }
}
