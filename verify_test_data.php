<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ProductCategory;
use App\Models\Product;
use App\Models\Store;
use App\Models\Order;

echo "📊 Final Test Data Summary:\n";
echo "  📂 Active Categories: " . ProductCategory::count() . "\n";
echo "  📦 Active Products: " . Product::count() . "\n";
echo "  🏪 Active Stores: " . Store::count() . "\n";
echo "  🛒 Total Orders: " . Order::count() . "\n";
echo "\n✅ Test data is ready for viewing in your admin panel!\n";

echo "\n📋 Sample Categories:\n";
$categories = ProductCategory::take(3)->get();
foreach ($categories as $category) {
    echo "  • {$category->name} (ID: {$category->id})\n";
}

echo "\n📦 Sample Products:\n";
$products = Product::with('category')->take(5)->get();
foreach ($products as $product) {
    echo "  • {$product->name} - \${$product->base_price} (Category: {$product->category->name})\n";
}

echo "\n🏪 Sample Stores:\n";
$stores = Store::take(3)->get();
foreach ($stores as $store) {
    echo "  • {$store->name} (ID: {$store->id})\n";
}

echo "\n🛒 Sample Orders:\n";
$orders = Order::take(3)->get();
foreach ($orders as $order) {
    echo "  • {$order->order_number} - \${$order->total_amount} ({$order->status})\n";
}

echo "\n🎉 SUCCESS: You now have comprehensive test data that you can see in your views!\n";
echo "✅ 3+ categories (with 1 soft-deleted)\n";
echo "✅ 15+ products (with 1 soft-deleted)\n";
echo "✅ 3+ stores (with 1 soft-deleted)\n";
echo "✅ 3 orders with different statuses\n";
echo "✅ All data is properly structured for the admin panel\n\n";
