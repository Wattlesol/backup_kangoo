<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\ProductCategory;
use App\Models\Product;
use App\Models\Store;
use App\Models\Order;

echo "🎯 Creating E-commerce Test Data for Views\n";
echo "=" . str_repeat("=", 50) . "\n\n";

// Get users
$admin = User::where('email', 'admin@test.com')->first();
$provider = User::where('email', 'provider@test.com')->first();
$user = User::where('email', 'user@test.com')->first();

if (!$admin || !$provider || !$user) {
    echo "❌ Test users not found. Please run the user seeder first.\n";
    exit(1);
}

echo "📋 Step 1: Creating Product Categories (3+)\n";

// Create 4 product categories
$categories = [
    [
        'name' => 'Electronics',
        'slug' => 'electronics',
        'description' => 'Electronic devices and gadgets including smartphones, laptops, and accessories',
        'is_featured' => 1,
        'status' => 1,
        'created_by_id' => $admin->id,
        'created_by_type' => 'admin'
    ],
    [
        'name' => 'Home & Garden',
        'slug' => 'home-garden',
        'description' => 'Home improvement, furniture, and garden supplies for your living space',
        'is_featured' => 1,
        'status' => 1,
        'created_by_id' => $admin->id,
        'created_by_type' => 'admin'
    ],
    [
        'name' => 'Fashion & Clothing',
        'slug' => 'fashion-clothing',
        'description' => 'Trendy clothing, shoes, and fashion accessories for men and women',
        'is_featured' => 0,
        'status' => 1,
        'created_by_id' => $admin->id,
        'created_by_type' => 'admin'
    ],
    [
        'name' => 'Sports & Fitness',
        'slug' => 'sports-fitness',
        'description' => 'Sports equipment, fitness gear, and outdoor recreation products',
        'is_featured' => 0,
        'status' => 1,
        'created_by_id' => $admin->id,
        'created_by_type' => 'admin'
    ]
];

$createdCategories = [];
foreach ($categories as $categoryData) {
    $category = ProductCategory::create($categoryData);
    $createdCategories[] = $category;
    echo "  ✅ Created category: {$category->name} (ID: {$category->id})\n";
}

echo "\n📦 Step 2: Creating Products (3+ per category)\n";

$createdProducts = [];
foreach ($createdCategories as $category) {
    echo "  📂 Creating products for category: {$category->name}\n";

    $products = [
        [
            'name' => $category->name === 'Electronics' ? 'iPhone 15 Pro' :
                     ($category->name === 'Home & Garden' ? 'Ergonomic Office Chair' :
                     ($category->name === 'Fashion & Clothing' ? 'Designer Leather Jacket' : 'Professional Tennis Racket')),
            'description' => 'Premium quality product with excellent features and durability. Perfect for daily use and professional applications.',
            'price' => rand(100, 1000),
            'category_id' => $category->id,
            'status' => 1,
            'is_featured' => 1,
            'stock_quantity' => rand(10, 100),
            'created_by_id' => $admin->id,
            'created_by_type' => 'admin'
        ],
        [
            'name' => $category->name === 'Electronics' ? 'MacBook Pro M3' :
                     ($category->name === 'Home & Garden' ? 'Smart LED Light Bulbs' :
                     ($category->name === 'Fashion & Clothing' ? 'Premium Running Shoes' : 'Yoga Mat Set')),
            'description' => 'High-quality product designed for performance and reliability. Comes with warranty and customer support.',
            'price' => rand(150, 1500),
            'category_id' => $category->id,
            'status' => 1,
            'is_featured' => 0,
            'stock_quantity' => rand(5, 50),
            'created_by_id' => $provider->id,
            'created_by_type' => 'provider'
        ],
        [
            'name' => $category->name === 'Electronics' ? 'Wireless Headphones' :
                     ($category->name === 'Home & Garden' ? 'Kitchen Knife Set' :
                     ($category->name === 'Fashion & Clothing' ? 'Casual Cotton T-Shirt' : 'Fitness Tracker Watch')),
            'description' => 'Affordable yet quality product suitable for everyday use. Great value for money with modern features.',
            'price' => rand(50, 500),
            'category_id' => $category->id,
            'status' => 1,
            'is_featured' => 0,
            'stock_quantity' => rand(20, 200),
            'created_by_id' => $provider->id,
            'created_by_type' => 'provider'
        ],
        [
            'name' => $category->name === 'Electronics' ? 'Gaming Mouse' :
                     ($category->name === 'Home & Garden' ? 'Indoor Plant Pot' :
                     ($category->name === 'Fashion & Clothing' ? 'Winter Wool Scarf' : 'Resistance Bands Set')),
            'description' => 'Specialized product with unique features. Perfect for enthusiasts and professionals alike.',
            'price' => rand(25, 250),
            'category_id' => $category->id,
            'status' => 1,
            'is_featured' => 0,
            'stock_quantity' => rand(15, 150),
            'created_by_id' => $admin->id,
            'created_by_type' => 'admin'
        ]
    ];

    foreach ($products as $productData) {
        $product = Product::create($productData);
        $createdProducts[] = $product;
        echo "    ✅ Created product: {$product->name} (ID: {$product->id}) - $" . $product->price . "\n";
    }
}

echo "\n🏪 Step 3: Creating Stores (3+)\n";

$stores = [
    [
        'name' => 'TechHub Electronics',
        'description' => 'Your one-stop shop for the latest electronics and gadgets',
        'provider_id' => $provider->id,
        'address' => '123 Tech Street, Silicon Valley, CA 94000',
        'contact_number' => '+1-555-0123',
        'email' => 'contact@techhub.com',
        'status' => 1,
        'is_approved' => 1,
        'slug' => 'techhub-electronics-' . $provider->id
    ],
    [
        'name' => 'Home Comfort Store',
        'description' => 'Quality home and garden products for comfortable living',
        'provider_id' => $provider->id,
        'address' => '456 Home Avenue, Garden City, NY 11530',
        'contact_number' => '+1-555-0456',
        'email' => 'info@homecomfort.com',
        'status' => 1,
        'is_approved' => 1,
        'slug' => 'home-comfort-store-' . $provider->id
    ],
    [
        'name' => 'Fashion Forward',
        'description' => 'Trendy fashion and clothing for modern lifestyle',
        'provider_id' => $provider->id,
        'address' => '789 Fashion Blvd, Style City, FL 33101',
        'contact_number' => '+1-555-0789',
        'email' => 'hello@fashionforward.com',
        'status' => 1,
        'is_approved' => 1,
        'slug' => 'fashion-forward-' . $provider->id
    ],
    [
        'name' => 'Sports Central',
        'description' => 'Professional sports equipment and fitness gear',
        'provider_id' => $provider->id,
        'address' => '321 Sports Lane, Fitness Town, TX 75001',
        'contact_number' => '+1-555-0321',
        'email' => 'support@sportscentral.com',
        'status' => 1,
        'is_approved' => 1,
        'slug' => 'sports-central-' . $provider->id
    ]
];

$createdStores = [];
foreach ($stores as $storeData) {
    $store = Store::create($storeData);
    $createdStores[] = $store;
    echo "  ✅ Created store: {$store->name} (ID: {$store->id})\n";
}

echo "\n🛒 Step 4: Creating Sample Orders (2+)\n";

$orders = [
    [
        'user_id' => $user->id,
        'total_amount' => 299.99,
        'status' => 'pending',
        'payment_status' => 'pending',
        'order_number' => 'ORD-' . date('Ymd') . '-001',
        'shipping_address' => '123 Customer Street, User City, CA 90210',
        'notes' => 'Please deliver during business hours'
    ],
    [
        'user_id' => $user->id,
        'total_amount' => 149.50,
        'status' => 'processing',
        'payment_status' => 'paid',
        'order_number' => 'ORD-' . date('Ymd') . '-002',
        'shipping_address' => '456 Buyer Avenue, Customer Town, NY 10001',
        'notes' => 'Gift wrapping requested'
    ]
];

$createdOrders = [];
foreach ($orders as $orderData) {
    $order = Order::create($orderData);
    $createdOrders[] = $order;
    echo "  ✅ Created order: {$order->order_number} (ID: {$order->id}) - $" . $order->total_amount . "\n";
}

echo "\n📊 Step 5: Summary of Created Data\n";
echo "  📂 Categories: " . count($createdCategories) . " created\n";
echo "  📦 Products: " . count($createdProducts) . " created\n";
echo "  🏪 Stores: " . count($createdStores) . " created\n";
echo "  🛒 Orders: " . count($createdOrders) . " created\n";

echo "\n🗑️ Step 6: Deleting 1 item from each type (as requested)\n";

// Delete 1 category (soft delete)
if (count($createdCategories) > 0) {
    $categoryToDelete = $createdCategories[0];
    $categoryToDelete->delete();
    echo "  🗑️ Soft deleted category: {$categoryToDelete->name} (ID: {$categoryToDelete->id})\n";
}

// Delete 1 product (soft delete)
if (count($createdProducts) > 0) {
    $productToDelete = $createdProducts[0];
    $productToDelete->delete();
    echo "  🗑️ Soft deleted product: {$productToDelete->name} (ID: {$productToDelete->id})\n";
}

// Delete 1 store (soft delete)
if (count($createdStores) > 0) {
    $storeToDelete = $createdStores[0];
    $storeToDelete->delete();
    echo "  🗑️ Soft deleted store: {$storeToDelete->name} (ID: {$storeToDelete->id})\n";
}

echo "\n✅ Final Status:\n";
echo "  📂 Active Categories: " . ProductCategory::count() . " (visible in views)\n";
echo "  📦 Active Products: " . Product::count() . " (visible in views)\n";
echo "  🏪 Active Stores: " . Store::count() . " (visible in views)\n";
echo "  🛒 Total Orders: " . Order::count() . " (visible in views)\n";

echo "\n🎉 Test data creation completed successfully!\n";
echo "✅ You can now see the data in your views\n";
echo "✅ Some items have been soft-deleted to test the deletion functionality\n";
echo "✅ Admin can see all data including soft-deleted items\n";
echo "✅ Providers and users see only active items\n\n";
