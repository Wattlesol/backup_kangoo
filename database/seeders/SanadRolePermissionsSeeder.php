<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SanadRolePermissionsSeeder extends Seeder
{
    public function run()
    {
        $adminPermissions = [
            'provider list',
            'provider add',
            'provider edit',
            'provider delete',
            'providerdocument list',
            'providerdocument add',
            'providerdocument edit',
            'providerdocument delete',
            'providerpayout list',
            'providerpayout add',
            'providerpayout edit',
            'providerpayout delete',
            'service list',
            'service add',
            'service edit',
            'service delete',
            'servicepackage list',
            'servicepackage add',
            'servicepackage edit',
            'servicepackage delete',
            'booking list',
            'booking add',
            'booking edit',
            'booking delete',
            'payment list',
            'payment add',
            'payment edit',
            'payment delete',
            'handyman list',
            'handyman add',
            'handyman edit',
            'handyman delete',
        ];

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

        $this->assignPermissions(['admin', 'demo_admin'], $adminPermissions);
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
}
