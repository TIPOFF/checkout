<?php

declare(strict_types=1);

use Tipoff\Authorization\Permissions\BasePermissionsMigration;

class AddCheckoutPermissions extends BasePermissionsMigration
{
    public function up()
    {
        $permissionsByRole = [
            'Admin' => [
                'view orders',
                'view order items',
            ],
            'Owner' => [
                'view orders',
                'view order items',
            ],
            'Former Staff' => [
                'view orders',
                'view order items',
            ],
            'Customer' => [
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
