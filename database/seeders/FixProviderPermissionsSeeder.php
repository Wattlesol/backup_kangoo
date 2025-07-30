<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class FixProviderPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds to fix provider permissions.
     *
     * @return void
     */
    public function run()
    {
        echo "Starting Provider Permission Fix...\n";

        // Ensure the provider role exists
        $providerRole = Role::firstOrCreate(['name' => 'provider']);
        echo "Provider role ensured: {$providerRole->name}\n";

        // Define required permissions for providers
        $requiredPermissions = [
            'product_category list',
            'product list',
            'product add',
            'product edit',
            'product delete',
            'product view',
            'store list',
            'store add',
            'store edit',
            'store view',
            'order list',
            'order view',
            'order status update',
            'provider_store manage',
            'provider_product manage',
            'provider_order manage',
        ];

        // Create permissions if they don't exist and assign to provider role
        foreach ($requiredPermissions as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            
            if (!$providerRole->hasPermissionTo($permission)) {
                $providerRole->givePermissionTo($permission);
                echo "Assigned permission '{$permissionName}' to provider role\n";
            }
        }

        // Find all users with user_type = 'provider' and assign the provider role
        $providerUsers = User::where('user_type', 'provider')->get();
        
        echo "Found {$providerUsers->count()} provider users\n";

        foreach ($providerUsers as $user) {
            if (!$user->hasRole('provider')) {
                $user->assignRole('provider');
                echo "Assigned provider role to user: {$user->email} (ID: {$user->id})\n";
            } else {
                echo "User {$user->email} already has provider role\n";
            }
        }

        echo "Provider permission fix completed!\n";
        echo "Summary:\n";
        echo "- Provider role permissions: " . $providerRole->permissions->count() . "\n";
        echo "- Provider users with role: " . User::role('provider')->count() . "\n";
    }
}
