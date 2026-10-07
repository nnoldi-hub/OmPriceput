<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'clients.view', 'clients.manage',
            'offers.view', 'offers.manage',
            'activities.view', 'activities.manage',
            'equipment.view', 'equipment.manage',
            'installations.view', 'installations.manage',
            'tickets.view', 'tickets.manage',
            'invoices.view', 'invoices.manage',
            'users.manage',
            'settings.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = [
            'admin' => $permissions,
            'vanzari' => [
                'clients.view', 'clients.manage',
                'offers.view', 'offers.manage',
                'activities.view', 'activities.manage',
                'installations.view',
            ],
            'tehnic' => [
                'clients.view',
                'equipment.view', 'equipment.manage',
                'installations.view', 'installations.manage',
                'tickets.view', 'tickets.manage',
            ],
            'suport' => [
                'clients.view',
                'installations.view',
                'tickets.view', 'tickets.manage',
            ],
            'client' => [],
            'client-manager' => [],
        ];

        foreach ($roles as $role => $rolePermissions) {
            $roleModel = Role::findOrCreate($role);
            $roleModel->syncPermissions($rolePermissions);
        }
    }
}
