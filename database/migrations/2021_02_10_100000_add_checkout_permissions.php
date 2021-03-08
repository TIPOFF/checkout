<?php

declare(strict_types=1);

use Tipoff\Authorization\Permissions\BasePermissionsMigration;

class AddCheckoutPermissions extends BasePermissionsMigration
{
    public function up()
    {
        $permissions = [
            'view orders',
            'view order items',
        ];
        $this->createPermissions($permissions);

        $permissionsByRole = [
            'Owner' => [
                'view orders',
                'view order items',
            ],
            'Staff' => [
                'view orders',
                'view order items',
            ],
        ];
        foreach ($permissionsByRole as $role => $permissions) {
            $this->addPermissionsToRole($permissions, $role);
        }
    }
}
