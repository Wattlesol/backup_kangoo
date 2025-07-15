<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\ProductCategory;
use App\Models\Product;
use App\Models\Store;

class SetupEcommerceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecommerce:setup {--fresh : Run fresh migrations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup e-commerce system with sample data';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Setting up e-commerce system...');

        // Run migrations if fresh option is provided
        if ($this->option('fresh')) {
            $this->info('Running fresh migrations...');
            Artisan::call('migrate:fresh');
            $this->info('Migrations completed.');
        }

        // Check if e-commerce tables exist
        $this->checkTables();

        // Run e-commerce permission seeder
        $this->info('Setting up permissions...');
        Artisan::call('db:seed', ['--class' => 'EcommercePermissionSeeder']);
        $this->info('Permissions created.');

        // Run e-commerce notification seeder
        $this->info('Setting up notification templates...');
        Artisan::call('db:seed', ['--class' => 'EcommerceNotificationSeeder']);
        $this->info('Notification templates created.');

        // Create sample data
        $this->createSampleData();

        $this->info('E-commerce system setup completed successfully!');
        $this->displaySummary();

        return 0;
    }

    /**
     * Check if required tables exist
     */
    private function checkTables()
    {
        $requiredTables = [
            'product_categories',
            'products',
            'product_variants',
            'stores',
            'store_products',
            'orders',
            'order_items',
            'order_status_histories',
            'shopping_carts'
        ];

        $missingTables = [];

        foreach ($requiredTables as $table) {
            if (!Schema::hasTable($table)) {
                $missingTables[] = $table;
            }
        }

        if (!empty($missingTables)) {
            $this->error('Missing required tables: ' . implode(', ', $missingTables));
            $this->error('Please run migrations first: php artisan migrate');
            exit(1);
        }

        $this->info('All required tables exist.');
    }

    /**
     * Create sample data for testing
     */
    private function createSampleData()
    {
        $this->info('Creating sample data...');

        // Create admin user if not exists
        $admin = User::where('user_type', 'admin')->first();
        if (!$admin) {
            $admin = User::create([
                'first_name' => 'Admin',
                'last_name' => 'User',
                'email' => 'admin@kangoo.com',
                'password' => bcrypt('password'),
                'user_type' => 'admin',
                'status' => 1
            ]);
            $this->info('Admin user created: admin@kangoo.com / password');
        }

        // Create provider user if not exists
        $provider = User::where('user_type', 'provider')->first();
        if (!$provider) {
            $provider = User::create([
                'first_name' => 'Provider',
                'last_name' => 'User',
                'email' => 'provider@kangoo.com',
                'password' => bcrypt('password'),
                'user_type' => 'provider',
                'status' => 1
            ]);
            $this->info('Provider user created: provider@kangoo.com / password');
        }

        // Create customer user if not exists
        $customer = User::where('user_type', 'user')->first();
        if (!$customer) {
            $customer = User::create([
                'first_name' => 'Customer',
                'last_name' => 'User',
                'email' => 'customer@kangoo.com',
                'password' => bcrypt('password'),
                'user_type' => 'user',
                'status' => 1
            ]);
            $this->info('Customer user created: customer@kangoo.com / password');
        }

        // Create sample categories
        $categories = [
            ['name' => 'Electronics', 'slug' => 'electronics'],
            ['name' => 'Clothing', 'slug' => 'clothing'],
            ['name' => 'Home & Garden', 'slug' => 'home-garden'],
            ['name' => 'Sports', 'slug' => 'sports'],
            ['name' => 'Books', 'slug' => 'books']
        ];

        foreach ($categories as $categoryData) {
            if (!ProductCategory::where('slug', $categoryData['slug'])->exists()) {
                ProductCategory::create([
                    'name' => $categoryData['name'],
                    'slug' => $categoryData['slug'],
                    'status' => 1,
                    'is_featured' => rand(0, 1)
                ]);
            }
        }
        $this->info('Sample categories created.');

        // Create sample products
        $electronicsCategory = ProductCategory::where('slug', 'electronics')->first();
        $clothingCategory = ProductCategory::where('slug', 'clothing')->first();

        $products = [
            [
                'name' => 'Smartphone',
                'slug' => 'smartphone',
                'sku' => 'PHONE-001',
                'category_id' => $electronicsCategory->id,
                'price' => 599.99,
                'stock' => 50
            ],
            [
                'name' => 'Laptop',
                'slug' => 'laptop',
                'sku' => 'LAPTOP-001',
                'category_id' => $electronicsCategory->id,
                'price' => 999.99,
                'stock' => 25
            ],
            [
                'name' => 'T-Shirt',
                'slug' => 't-shirt',
                'sku' => 'SHIRT-001',
                'category_id' => $clothingCategory->id,
                'price' => 29.99,
                'stock' => 100
            ]
        ];

        foreach ($products as $productData) {
            if (!Product::where('slug', $productData['slug'])->exists()) {
                Product::create([
                    'name' => $productData['name'],
                    'slug' => $productData['slug'],
                    'sku' => $productData['sku'],
                    'product_category_id' => $productData['category_id'],
                    'base_price' => $productData['price'],
                    'stock_quantity' => $productData['stock'],
                    'status' => 1,
                    'created_by' => $admin->id,
                    'created_by_type' => 'admin'
                ]);
            }
        }
        $this->info('Sample products created.');

        // Create sample store
        if (!Store::where('provider_id', $provider->id)->exists()) {
            Store::create([
                'name' => 'Sample Store',
                'slug' => 'sample-store',
                'provider_id' => $provider->id,
                'address' => '123 Sample Street, Sample City',
                'phone' => '+1234567890',
                'status' => 'approved'
            ]);
            $this->info('Sample store created.');
        }
    }

    /**
     * Display setup summary
     */
    private function displaySummary()
    {
        $this->info('');
        $this->info('=== E-COMMERCE SETUP SUMMARY ===');
        $this->info('Categories: ' . ProductCategory::count());
        $this->info('Products: ' . Product::count());
        $this->info('Stores: ' . Store::count());
        $this->info('Orders: ' . \App\Models\Order::count());
        $this->info('');
        $this->info('Test Users:');
        $this->info('- Admin: admin@kangoo.com / password');
        $this->info('- Provider: provider@kangoo.com / password');
        $this->info('- Customer: customer@kangoo.com / password');
        $this->info('');
        $this->info('Available Routes:');
        $this->info('- Products: /products');
        $this->info('- Stores: /stores');
        $this->info('- Admin Panel: /product, /store, /order, /dynamic-pricing');
        $this->info('- Provider Panel: /provider/store, /provider/product, /provider/orders');
        $this->info('');
        $this->info('API Endpoints:');
        $this->info('- GET /api/products');
        $this->info('- GET /api/stores');
        $this->info('- POST /api/cart/add');
        $this->info('- POST /api/orders');
        $this->info('');
        $this->info('Run tests: php artisan test --filter EcommerceIntegrationTest');
    }
}
