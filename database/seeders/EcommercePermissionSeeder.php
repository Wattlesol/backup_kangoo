<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class EcommercePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        echo "Creating E-commerce permissions...\n";

        // Define e-commerce permissions
        $permissions = [
            // Product Category Permissions
            'product_category list',
            'product_category add',
            'product_category edit',
            'product_category delete',

            // Product Permissions
            'product list',
            'product add', 
            'product edit',
            'product delete',
            'product view',

            // Store Permissions
            'store list',
            'store add',
            'store edit', 
            'store delete',
            'store view',
            'store approve',
            'store suspend',

            // Order Permissions
            'order list',
            'order add',
            'order edit',
            'order delete',
            'order view',
            'order status update',

            // Dynamic Pricing Permissions (Admin only)
            'dynamic_pricing list',
            'dynamic_pricing edit',
            'dynamic_pricing analytics',

            // Provider Store Permissions
            'provider_store manage',
            'provider_product manage',
            'provider_order manage',
        ];

        // Create permissions if they don't exist
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
            echo "Created permission: {$permission}\n";
        }

        // Get or create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $providerRole = Role::firstOrCreate(['name' => 'provider']);

        echo "Assigning permissions to Admin role...\n";
        
        // Admin gets all e-commerce permissions
        $adminPermissions = [
            // Full access to categories
            'product_category list',
            'product_category add',
            'product_category edit',
            'product_category delete',

            // Full access to products
            'product list',
            'product add',
            'product edit', 
            'product delete',
            'product view',

            // Full access to stores
            'store list',
            'store add',
            'store edit',
            'store delete',
            'store view',
            'store approve',
            'store suspend',

            // Full access to orders
            'order list',
            'order add',
            'order edit',
            'order delete',
            'order view',
            'order status update',

            // Exclusive access to dynamic pricing
            'dynamic_pricing list',
            'dynamic_pricing edit',
            'dynamic_pricing analytics',

            // Can manage provider stores/products
            'provider_store manage',
            'provider_product manage',
            'provider_order manage',
        ];

        foreach ($adminPermissions as $permission) {
            $adminRole->givePermissionTo($permission);
        }

        echo "Assigning permissions to Provider role...\n";

        // Provider gets limited permissions
        $providerPermissions = [
            // Can view categories (to assign products)
            'product_category list',

            // Can manage their own products
            'product list',
            'product add',
            'product edit',
            'product delete',
            'product view',

            // Can manage their own store
            'store list',
            'store add',
            'store edit',
            'store view',

            // Can manage their orders
            'order list',
            'order view',
            'order status update',

            // Provider-specific permissions
            'provider_store manage',
            'provider_product manage', 
            'provider_order manage',
        ];

        foreach ($providerPermissions as $permission) {
            $providerRole->givePermissionTo($permission);
        }

        // Assign roles to existing users if they exist
        $this->assignRolesToUsers();

        // Enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        echo "E-commerce permissions setup completed!\n\n";
        $this->displayPermissionSummary();
    }

    /**
     * Assign roles to existing users
     */
    private function assignRolesToUsers()
    {
        echo "Assigning roles to existing users...\n";

        // Get user model
        $userModel = config('auth.providers.users.model', 'App\Models\User');
        
        if (!class_exists($userModel)) {
            echo "User model not found, skipping user role assignment.\n";
            return;
        }

        // Assign admin role to admin users
        $adminUsers = $userModel::where('user_type', 'admin')->get();
        foreach ($adminUsers as $user) {
            if (!$user->hasRole('admin')) {
                $user->assignRole('admin');
                echo "Assigned admin role to: {$user->email}\n";
            }
        }

        // Assign provider role to provider users
        $providerUsers = $userModel::where('user_type', 'provider')->get();
        foreach ($providerUsers as $user) {
            if (!$user->hasRole('provider')) {
                $user->assignRole('provider');
                echo "Assigned provider role to: {$user->email}\n";
            }
        }

        // Assign user role to regular users (if role exists)
        $userRole = Role::where('name', 'user')->first();
        if ($userRole) {
            $regularUsers = $userModel::where('user_type', 'user')->get();
            foreach ($regularUsers as $user) {
                if (!$user->hasRole('user')) {
                    $user->assignRole('user');
                    echo "Assigned user role to: {$user->email}\n";
                }
            }
        }
    }

    /**
     * Display permission summary
     */
    private function displayPermissionSummary()
    {
        echo "=== E-COMMERCE PERMISSION SUMMARY ===\n\n";
        
        echo "ADMIN PERMISSIONS:\n";
        echo "✓ Full access to Product Categories (CRUD)\n";
        echo "✓ Full access to Products (CRUD)\n";
        echo "✓ Full access to Stores (CRUD + Approve/Suspend)\n";
        echo "✓ Full access to Orders (CRUD + Status Updates)\n";
        echo "✓ Exclusive access to Dynamic Pricing\n";
        echo "✓ Can manage Provider stores and products\n\n";

        echo "PROVIDER PERMISSIONS:\n";
        echo "✓ View Product Categories\n";
        echo "✓ Manage own Products (CRUD)\n";
        echo "✓ Manage own Store (Create/Edit/View)\n";
        echo "✓ Manage own Orders (View + Status Updates)\n";
        echo "✗ No access to Dynamic Pricing\n";
        echo "✗ Cannot approve/suspend stores\n\n";

        echo "BUSINESS LOGIC:\n";
        echo "• Admin can create stores for providers\n";
        echo "• Providers can create their own stores\n";
        echo "• Admin products automatically go to admin store\n";
        echo "• Admin can add/edit/delete products in any store\n";
        echo "• Providers can only manage their own store products\n";
        echo "• Dynamic pricing is admin-only feature\n";
        echo "• Store approval/suspension is admin-only\n\n";
    }
}
