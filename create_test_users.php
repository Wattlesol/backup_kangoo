<?php

/**
 * Create Test Users Script
 *
 * This script creates test users for e-commerce testing
 */

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateTestUsers {

    public function __construct() {
        // Bootstrap Laravel
        $app = require_once 'bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

        echo "👤 Creating test users for e-commerce testing...\n";
        echo "=" . str_repeat("=", 50) . "\n\n";
    }

    public function createUsers() {
        try {
            // Create admin user
            $adminId = DB::table('users')->insertGetId([
                'username' => 'testadmin',
                'first_name' => 'Test',
                'last_name' => 'Admin',
                'email' => 'admin@test.com',
                'password' => Hash::make('password123'),
                'user_type' => 'admin',
                'contact_number' => '1234567890',
                'display_name' => 'Test Admin',
                'status' => 1,
                'is_email_verified' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            echo "✅ Created admin user: admin@test.com (ID: $adminId)\n";

            // Create regular user
            $userId = DB::table('users')->insertGetId([
                'username' => 'testuser',
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'user@test.com',
                'password' => Hash::make('password123'),
                'user_type' => 'user',
                'contact_number' => '1234567891',
                'display_name' => 'Test User',
                'status' => 1,
                'is_email_verified' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            echo "✅ Created regular user: user@test.com (ID: $userId)\n";

            // Create provider user
            $providerId = DB::table('users')->insertGetId([
                'username' => 'testprovider',
                'first_name' => 'Test',
                'last_name' => 'Provider',
                'email' => 'provider@test.com',
                'password' => Hash::make('password123'),
                'user_type' => 'provider',
                'contact_number' => '1234567892',
                'display_name' => 'Test Provider',
                'status' => 1,
                'is_email_verified' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            echo "✅ Created provider user: provider@test.com (ID: $providerId)\n";

            // Add some basic location data
            $countryId = DB::table('countries')->insertGetId([
                'name' => 'Test Country',
                'code' => 'TC'
            ]);

            $stateId = DB::table('states')->insertGetId([
                'name' => 'Test State',
                'country_id' => $countryId
            ]);

            $cityId = DB::table('cities')->insertGetId([
                'name' => 'Test City',
                'state_id' => $stateId
            ]);

            echo "✅ Created location data: Country ($countryId), State ($stateId), City ($cityId)\n";

            echo "\n🎉 All test users created successfully!\n";
            echo "📋 Login Credentials:\n";
            echo "  Admin: admin@test.com / password123\n";
            echo "  User: user@test.com / password123\n";
            echo "  Provider: provider@test.com / password123\n\n";

            return true;

        } catch (Exception $e) {
            echo "\n❌ Failed to create test users: " . $e->getMessage() . "\n";
            return false;
        }
    }
}

// Run the script
$creator = new CreateTestUsers();
$success = $creator->createUsers();

if ($success) {
    echo "🎊 TEST USERS READY: E-commerce system is ready for testing!\n";
    exit(0);
} else {
    echo "❌ TEST USER CREATION FAILED: Fix issues before testing.\n";
    exit(1);
}
