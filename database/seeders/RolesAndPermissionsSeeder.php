<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'access-admin-panel',
            'manage-products',
            'manage-brands',
            'manage-sizes',
            'manage-categories',
            'manage-customers',
            'manage-providers',
            'manage-lots',
            'manage-purchase-expense-concepts',
            'manage-users',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $customer = Role::findOrCreate('customer', 'web');
        $staff = Role::findOrCreate('staff', 'web');
        $admin = Role::findOrCreate('admin', 'web');

        $staff->syncPermissions([
            'access-admin-panel',
            'manage-products',
            'manage-brands',
            'manage-sizes',
            'manage-categories',
            'manage-lots',
            'manage-purchase-expense-concepts',
        ]);

        $admin->syncPermissions($permissions);

        $customer->syncPermissions([]);
    }
}
