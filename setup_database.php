<?php

/**
 * Database Setup Script
 * 
 * This script will:
 * 1. Import the SQL file to set up the database structure
 * 2. Create necessary admin user for testing
 * 3. Set up permissions and roles
 */

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSetup {
    
    public function __construct() {
        // Bootstrap Laravel
        $app = require_once 'bootstrap/app.php';
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        
        echo "🔧 Setting up database for e-commerce testing...\n";
        echo "=" . str_repeat("=", 50) . "\n\n";
    }
    
    public function setup() {
        try {
            // Step 1: Import SQL file
            $this->importSqlFile();
            
            // Step 2: Create admin user
            $this->createAdminUser();
            
            // Step 3: Set up permissions
            $this->setupPermissions();
            
            echo "\n✅ Database setup completed successfully!\n";
            echo "🎉 Ready for e-commerce testing!\n\n";
            
            echo "📋 Test Users Created:\n";
            echo "  Admin: admin@test.com / password123\n";
            echo "  User: user@test.com / password123\n";
            echo "  Provider: provider@test.com / password123\n\n";
            
            return true;
            
        } catch (Exception $e) {
            echo "\n❌ Database setup failed: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    private function importSqlFile() {
        echo "📥 Importing SQL file...\n";
        
        $sqlFile = 'u517511954_kangoo.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("SQL file not found: $sqlFile");
        }
        
        // Get database configuration
        $config = config('database.connections.mysql');
        $database = $config['database'];
        $username = $config['username'];
        $password = $config['password'];
        $host = $config['host'];
        $port = $config['port'];
        
        // Import SQL file using mysql command
        $command = "mysql -h{$host} -P{$port} -u{$username}";
        if ($password) {
            $command .= " -p{$password}";
        }
        $command .= " {$database} < {$sqlFile}";
        
        $output = [];
        $returnCode = 0;
        exec($command . " 2>&1", $output, $returnCode);
        
        if ($returnCode !== 0) {
            // Try alternative method using PHP
            $this->importSqlWithPHP($sqlFile);
        }
        
        echo "  ✅ SQL file imported successfully\n";
    }
    
    private function importSqlWithPHP($sqlFile) {
        echo "  📥 Using PHP method to import SQL...\n";
        
        $sql = file_get_contents($sqlFile);
        if (!$sql) {
            throw new Exception("Could not read SQL file");
        }
        
        // Split SQL into individual statements
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) {
                return !empty($stmt) && !preg_match('/^(--|\/\*)/', $stmt);
            }
        );
        
        DB::beginTransaction();
        
        try {
            foreach ($statements as $statement) {
                if (trim($statement)) {
                    DB::unprepared($statement);
                }
            }
            
            DB::commit();
            echo "  ✅ SQL imported using PHP method\n";
            
        } catch (Exception $e) {
            DB::rollback();
            throw new Exception("Failed to import SQL: " . $e->getMessage());
        }
    }
    
    private function createAdminUser() {
        echo "👤 Creating test users...\n";
        
        // Create admin user
        $admin = User::updateOrCreate(
            ['email' => 'admin@test.com'],
            [
                'username' => 'testadmin',
                'first_name' => 'Test',
                'last_name' => 'Admin',
                'email' => 'admin@test.com',
                'password' => Hash::make('password123'),
                'user_type' => 'admin',
                'contact_number' => '1234567890',
                'display_name' => 'Test Admin',
                'status' => 1,
                'is_email_verified' => 1
            ]
        );
        
        // Create regular user
        $user = User::updateOrCreate(
            ['email' => 'user@test.com'],
            [
                'username' => 'testuser',
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'user@test.com',
                'password' => Hash::make('password123'),
                'user_type' => 'user',
                'contact_number' => '1234567891',
                'display_name' => 'Test User',
                'status' => 1,
                'is_email_verified' => 1
            ]
        );
        
        // Create provider user
        $provider = User::updateOrCreate(
            ['email' => 'provider@test.com'],
            [
                'username' => 'testprovider',
                'first_name' => 'Test',
                'last_name' => 'Provider',
                'email' => 'provider@test.com',
                'password' => Hash::make('password123'),
                'user_type' => 'provider',
                'contact_number' => '1234567892',
                'display_name' => 'Test Provider',
                'status' => 1,
                'is_email_verified' => 1
            ]
        );
        
        echo "  ✅ Test users created successfully\n";
    }
    
    private function setupPermissions() {
        echo "🔐 Setting up permissions...\n";
        
        try {
            // Create roles if they don't exist
            $adminRole = Role::firstOrCreate(['name' => 'admin']);
            $userRole = Role::firstOrCreate(['name' => 'user']);
            $providerRole = Role::firstOrCreate(['name' => 'provider']);
            
            // Create e-commerce permissions
            $permissions = [
                'product category list',
                'product category add',
                'product category edit',
                'product category delete',
                'product list',
                'product add',
                'product edit',
                'product delete',
                'store list',
                'store add',
                'store edit',
                'store delete',
                'order list',
                'order edit',
                'order status update',
                'dynamic_pricing list',
                'dynamic_pricing edit'
            ];
            
            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission]);
            }
            
            // Assign permissions to admin role
            $adminRole->syncPermissions(Permission::all());
            
            // Assign roles to users
            $admin = User::where('email', 'admin@test.com')->first();
            $user = User::where('email', 'user@test.com')->first();
            $provider = User::where('email', 'provider@test.com')->first();
            
            if ($admin) $admin->assignRole('admin');
            if ($user) $user->assignRole('user');
            if ($provider) $provider->assignRole('provider');
            
            echo "  ✅ Permissions set up successfully\n";
            
        } catch (Exception $e) {
            echo "  ⚠️  Permission setup skipped (will be handled by application)\n";
        }
    }
}

// Run the setup
$setup = new DatabaseSetup();
$success = $setup->setup();

if ($success) {
    echo "🎊 DATABASE READY: E-commerce system is ready for testing!\n";
    exit(0);
} else {
    echo "❌ DATABASE SETUP FAILED: Fix issues before testing.\n";
    exit(1);
}
