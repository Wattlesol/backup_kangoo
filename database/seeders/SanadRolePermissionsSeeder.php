<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SanadRolePermissionsSeeder extends Seeder
{
    public function run()
    {
        $allPermissions = Permission::all();
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions($allPermissions);
        $demoAdminRole = Role::firstOrCreate(['name' => 'demo_admin']);
        $demoAdminRole->syncPermissions($allPermissions);

        $providerPermissions = [
            'service list',
            'service add',
            'service edit',
            'service delete',
            'servicepackage list',
            'servicepackage add',
            'servicepackage edit',
            'servicepackage delete',
            'service add on list',
            'service add on add',
            'service add on edit',
            'service add on delete',
            'booking list',
            'booking edit',
            'payment list',
            'handyman list',
            'handyman add',
            'handyman edit',
            'handyman delete',
        ];
        $this->assignPermissions(['provider'], $providerPermissions);
        $this->assignPermissions(['handyman'], [
            'booking list',
            'booking edit',
            'payment list',
        ]);
        $this->assignPermissions(['user', 'customer'], [
            'booking list',
            'booking add',
            'booking edit',
            'service list',
            'payment list',
        ]);

        $this->syncExistingUserRoles();
    }

    private function assignPermissions(array $roleNames, array $permissionNames)
    {
        foreach ($roleNames as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName]);

            foreach ($permissionNames as $permissionName) {
                $permission = Permission::firstOrCreate(['name' => $permissionName]);

                if (!$role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
        }
    }

    private function syncExistingUserRoles(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $validRoles = ['admin', 'demo_admin', 'provider', 'handyman', 'user'];

        User::withTrashed()->chunkById(100, function ($users) use ($validRoles) {
            foreach ($users as $user) {
                $role = in_array($user->user_type, ['customer', 'user'], true)
                    ? 'user'
                    : $user->user_type;

                if (in_array($role, $validRoles, true)) {
                    $user->syncRoles([$role]);
                }
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
