<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;

class DemoUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create demo users for testing different roles
        $demoUsers = [
            [
                'first_name' => 'Demo',
                'last_name' => 'Admin',
                'email' => 'demo@admin.com',
                'password' => Hash::make('12345678'),
                'user_type' => 'admin',
                'email_verified_at' => now(),
                'status' => 1,
                'display_name' => 'Demo Admin',
                'username' => 'demo_admin',
                'contact_number' => '+1234567890',
                'country_id' => 1,
                'state_id' => 1,
                'city_id' => 1,
                'address' => '123 Admin Street',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'first_name' => 'Demo',
                'last_name' => 'Provider',
                'email' => 'demo@provider.com',
                'password' => Hash::make('12345678'),
                'user_type' => 'provider',
                'email_verified_at' => now(),
                'status' => 1,
                'display_name' => 'Demo Provider',
                'username' => 'demo_provider',
                'contact_number' => '+1234567891',
                'country_id' => 1,
                'state_id' => 1,
                'city_id' => 1,
                'address' => '123 Provider Street',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'first_name' => 'Demo',
                'last_name' => 'Handyman',
                'email' => 'demo@handyman.com',
                'password' => Hash::make('12345678'),
                'user_type' => 'handyman',
                'email_verified_at' => now(),
                'status' => 1,
                'display_name' => 'Demo Handyman',
                'username' => 'demo_handyman',
                'contact_number' => '+1234567892',
                'country_id' => 1,
                'state_id' => 1,
                'city_id' => 1,
                'address' => '123 Handyman Street',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'first_name' => 'Demo',
                'last_name' => 'Customer',
                'email' => 'demo@customer.com',
                'password' => Hash::make('12345678'),
                'user_type' => 'customer',
                'email_verified_at' => now(),
                'status' => 1,
                'display_name' => 'Demo Customer',
                'username' => 'demo_customer',
                'contact_number' => '+1234567893',
                'country_id' => 1,
                'state_id' => 1,
                'city_id' => 1,
                'address' => '123 Customer Street',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($demoUsers as $userData) {
            // Check if user already exists
            $existingUser = User::where('email', $userData['email'])->first();
            
            if (!$existingUser) {
                $user = User::create($userData);
                $this->command->info("Created demo user: {$userData['email']} ({$userData['user_type']})");
            } else {
                $user = $existingUser;
                $this->command->info("Demo user already exists: {$userData['email']}");
            }

            // The demo admin is a deliberate full-access QA account. Repair its
            // role and permissions even when the account already exists.
            if ($userData['email'] === 'demo@admin.com') {
                $user->forceFill(['user_type' => 'demo_admin', 'status' => 1])->save();
                $role = Role::firstOrCreate(['name' => 'demo_admin', 'guard_name' => 'web']);
                $role->syncPermissions(\Spatie\Permission\Models\Permission::all());
                $user->syncRoles([$role]);
            } else {
                $role = Role::firstOrCreate(['name' => $userData['user_type'], 'guard_name' => 'web']);
                $user->syncRoles([$role]);
            }
        }
        
        $this->command->info('Demo users seeder completed!');
        $this->command->info('');
        $this->command->info('Demo Login Credentials:');
        $this->command->info('======================');
        $this->command->info('Admin:    demo@admin.com    / 12345678');
        $this->command->info('Provider: demo@provider.com / 12345678');
        $this->command->info('Handyman: demo@handyman.com / 12345678');
        $this->command->info('Customer: demo@customer.com / 12345678');
    }
}
