<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\User;
use App\Models\ProductCategory;

class ProductApprovalTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get a provider user (or create one if none exists)
        $provider = User::where('user_type', 'provider')->first();
        
        if (!$provider) {
            $provider = User::create([
                'first_name' => 'Test',
                'last_name' => 'Provider',
                'display_name' => 'Test Provider',
                'email' => 'testprovider@example.com',
                'password' => bcrypt('password'),
                'user_type' => 'provider',
                'status' => 1,
                'is_featured' => 0,
                'email_verified_at' => now()
            ]);
        }

        // Get a category (or create one if none exists)
        $category = ProductCategory::first();
        
        if (!$category) {
            $category = ProductCategory::create([
                'name' => 'Electronics',
                'description' => 'Electronic products',
                'status' => 1,
                'is_featured' => 1
            ]);
        }

        // Create test products with different approval statuses
        $testProducts = [
            [
                'name' => 'Wireless Bluetooth Headphones',
                'description' => 'High-quality wireless headphones with noise cancellation',
                'short_description' => 'Premium wireless headphones',
                'sku' => 'WBH-001',
                'base_price' => 99.99,
                'selling_price' => 89.99,
                'approval_status' => 'pending',
                'status' => false,
                'is_available' => false
            ],
            [
                'name' => 'Smart Fitness Tracker',
                'description' => 'Track your fitness goals with this advanced smart tracker',
                'short_description' => 'Advanced fitness tracking device',
                'sku' => 'SFT-002',
                'base_price' => 149.99,
                'selling_price' => 129.99,
                'approval_status' => 'pending',
                'status' => false,
                'is_available' => false
            ],
            [
                'name' => 'Portable Power Bank',
                'description' => 'High-capacity portable charger for all your devices',
                'short_description' => '20000mAh portable power bank',
                'sku' => 'PPB-003',
                'base_price' => 39.99,
                'selling_price' => 34.99,
                'approval_status' => 'approved',
                'status' => true,
                'is_available' => true,
                'approved_at' => now()->subDays(2),
                'approved_by' => 1 // Assuming admin user ID is 1
            ],
            [
                'name' => 'Gaming Mouse Pad',
                'description' => 'Large gaming mouse pad with RGB lighting',
                'short_description' => 'RGB gaming mouse pad',
                'sku' => 'GMP-004',
                'base_price' => 29.99,
                'selling_price' => 24.99,
                'approval_status' => 'rejected',
                'status' => false,
                'is_available' => false,
                'rejected_at' => now()->subDays(1),
                'rejected_by' => 1, // Assuming admin user ID is 1
                'rejection_reason' => 'Product images are not clear enough. Please provide high-quality product photos showing all angles.'
            ]
        ];

        foreach ($testProducts as $productData) {
            Product::create(array_merge($productData, [
                'provider_id' => $provider->id,
                'product_category_id' => $category->id,
                'created_by' => $provider->id,
                'created_by_type' => 'provider',
                'slug' => \Str::slug($productData['name']),
                'stock_quantity' => 100,
                'minimum_order_quantity' => 1,
                'track_inventory' => true,
                'is_featured' => false,
                'meta_data' => json_encode([]),
                'sort_order' => 0
            ]));
        }

        $this->command->info('Created test products for approval system testing');
        $this->command->info('- 2 Pending products');
        $this->command->info('- 1 Approved product');
        $this->command->info('- 1 Rejected product');
    }
}
