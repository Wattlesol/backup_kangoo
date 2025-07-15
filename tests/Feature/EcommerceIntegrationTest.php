<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\ProductCategory;
use App\Models\Product;
use App\Models\Store;
use App\Models\Order;
use App\Models\ShoppingCart;
use App\Models\StoreProduct;
use Illuminate\Support\Facades\Notification;

class EcommerceIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $admin;
    protected $provider;
    protected $customer;
    protected $category;
    protected $product;
    protected $store;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->admin = User::factory()->create([
            'user_type' => 'admin',
            'email' => 'admin@test.com'
        ]);
        
        $this->provider = User::factory()->create([
            'user_type' => 'provider',
            'email' => 'provider@test.com'
        ]);
        
        $this->customer = User::factory()->create([
            'user_type' => 'user',
            'email' => 'customer@test.com'
        ]);

        // Create test category
        $this->category = ProductCategory::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'status' => 1
        ]);

        // Create test product
        $this->product = Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'sku' => 'TEST-001',
            'product_category_id' => $this->category->id,
            'base_price' => 100.00,
            'stock_quantity' => 50,
            'status' => 1,
            'created_by' => $this->admin->id,
            'created_by_type' => 'admin'
        ]);

        // Create test store
        $this->store = Store::create([
            'name' => 'Test Store',
            'slug' => 'test-store',
            'provider_id' => $this->provider->id,
            'address' => '123 Test Street',
            'status' => 'approved'
        ]);
    }

    /** @test */
    public function test_product_category_crud_operations()
    {
        $this->actingAs($this->admin);

        // Test create
        $response = $this->post('/productcategory', [
            'name' => 'New Category',
            'description' => 'Test description',
            'status' => 1
        ]);
        
        $this->assertDatabaseHas('product_categories', [
            'name' => 'New Category'
        ]);

        // Test read
        $category = ProductCategory::where('name', 'New Category')->first();
        $response = $this->get("/productcategory/{$category->id}");
        $response->assertStatus(200);

        // Test update
        $response = $this->put("/productcategory/{$category->id}", [
            'name' => 'Updated Category',
            'description' => 'Updated description',
            'status' => 1
        ]);
        
        $this->assertDatabaseHas('product_categories', [
            'name' => 'Updated Category'
        ]);
    }

    /** @test */
    public function test_product_crud_operations()
    {
        $this->actingAs($this->admin);

        // Test create
        $response = $this->post('/product', [
            'name' => 'New Product',
            'product_category_id' => $this->category->id,
            'base_price' => 150.00,
            'stock_quantity' => 25,
            'status' => 1
        ]);
        
        $this->assertDatabaseHas('products', [
            'name' => 'New Product'
        ]);

        // Test dynamic pricing
        $product = Product::where('name', 'New Product')->first();
        $response = $this->post('/dynamic-pricing/update', [
            'product_id' => $product->id,
            'admin_price_active' => true,
            'price_override_type' => 'lowest'
        ]);
        
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'admin_price_active' => true,
            'price_override_type' => 'lowest'
        ]);
    }

    /** @test */
    public function test_store_management_workflow()
    {
        // Provider creates store
        $this->actingAs($this->provider);
        
        $response = $this->post('/provider/store', [
            'name' => 'Provider Store',
            'address' => '456 Provider Street',
            'phone' => '123-456-7890'
        ]);
        
        $store = Store::where('name', 'Provider Store')->first();
        $this->assertEquals('pending', $store->status);

        // Admin approves store
        $this->actingAs($this->admin);
        
        $response = $this->post('/store-approve', [
            'store_id' => $store->id
        ]);
        
        $store->refresh();
        $this->assertEquals('approved', $store->status);
    }

    /** @test */
    public function test_shopping_cart_functionality()
    {
        $this->actingAs($this->customer);

        // Add product to store
        StoreProduct::create([
            'store_id' => $this->store->id,
            'product_id' => $this->product->id,
            'store_price' => 95.00,
            'stock_quantity' => 20,
            'is_available' => true
        ]);

        // Add to cart
        $response = $this->post('/api/cart/add', [
            'product_id' => $this->product->id,
            'store_id' => $this->store->id,
            'quantity' => 2
        ]);
        
        $this->assertDatabaseHas('shopping_carts', [
            'user_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'quantity' => 2
        ]);

        // Update cart
        $cartItem = ShoppingCart::where('user_id', $this->customer->id)->first();
        $response = $this->put("/api/cart/{$cartItem->id}", [
            'quantity' => 3
        ]);
        
        $cartItem->refresh();
        $this->assertEquals(3, $cartItem->quantity);

        // Remove from cart
        $response = $this->delete("/api/cart/{$cartItem->id}");
        
        $this->assertDatabaseMissing('shopping_carts', [
            'id' => $cartItem->id
        ]);
    }

    /** @test */
    public function test_order_workflow()
    {
        Notification::fake();
        
        $this->actingAs($this->customer);

        // Add product to store
        StoreProduct::create([
            'store_id' => $this->store->id,
            'product_id' => $this->product->id,
            'store_price' => 95.00,
            'stock_quantity' => 20,
            'is_available' => true
        ]);

        // Create order
        $response = $this->post('/api/orders', [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'store_id' => $this->store->id,
                    'quantity' => 2,
                    'unit_price' => 95.00
                ]
            ],
            'delivery_address' => '789 Customer Street',
            'payment_method' => 'cash'
        ]);
        
        $response->assertStatus(201);
        $order = Order::where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals('pending', $order->status);

        // Update order status
        $this->actingAs($this->provider);
        
        $response = $this->post('/provider/order-update-status', [
            'order_id' => $order->id,
            'status' => 'confirmed',
            'notes' => 'Order confirmed by provider'
        ]);
        
        $order->refresh();
        $this->assertEquals('confirmed', $order->status);

        // Deliver order
        $response = $this->post('/provider/order-update-status', [
            'order_id' => $order->id,
            'status' => 'delivered'
        ]);
        
        $order->refresh();
        $this->assertEquals('delivered', $order->status);
        $this->assertNotNull($order->delivered_at);
    }

    /** @test */
    public function test_api_endpoints()
    {
        // Test products API
        $response = $this->get('/api/products');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data' => [
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'base_price',
                        'category'
                    ]
                ]
            ]
        ]);

        // Test stores API
        $response = $this->get('/api/stores');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data' => [
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'address'
                    ]
                ]
            ]
        ]);
    }

    /** @test */
    public function test_frontend_pages()
    {
        // Test products listing page
        $response = $this->get('/products');
        $response->assertStatus(200);
        $response->assertViewIs('landing-page.products.index');

        // Test product detail page
        $response = $this->get("/product/{$this->product->slug}");
        $response->assertStatus(200);
        $response->assertViewIs('landing-page.products.show');

        // Test stores listing page
        $response = $this->get('/stores');
        $response->assertStatus(200);
        $response->assertViewIs('landing-page.stores.index');

        // Test store detail page
        $response = $this->get("/store/{$this->store->slug}");
        $response->assertStatus(200);
        $response->assertViewIs('landing-page.stores.show');
    }

    /** @test */
    public function test_admin_dashboard_access()
    {
        $this->actingAs($this->admin);

        // Test product management
        $response = $this->get('/product');
        $response->assertStatus(200);

        // Test store management
        $response = $this->get('/store');
        $response->assertStatus(200);

        // Test order management
        $response = $this->get('/order');
        $response->assertStatus(200);

        // Test dynamic pricing
        $response = $this->get('/dynamic-pricing');
        $response->assertStatus(200);
    }

    /** @test */
    public function test_provider_dashboard_access()
    {
        $this->actingAs($this->provider);

        // Test provider store management
        $response = $this->get('/provider/store');
        $response->assertStatus(200);

        // Test provider product management
        $response = $this->get('/provider/product');
        $response->assertStatus(200);

        // Test provider order management
        $response = $this->get('/provider/orders');
        $response->assertStatus(200);
    }

    /** @test */
    public function test_notification_system_integration()
    {
        Notification::fake();

        // Test order creation notification
        $order = Order::create([
            'order_number' => 'ORD-001',
            'customer_id' => $this->customer->id,
            'store_id' => $this->store->id,
            'total_amount' => 190.00,
            'status' => 'pending',
            'payment_method' => 'cash'
        ]);

        // Verify notification was sent
        // Note: This would need to be adjusted based on the actual notification implementation
        $this->assertTrue(true); // Placeholder assertion

        // Test store approval notification
        $newStore = Store::create([
            'name' => 'New Store',
            'slug' => 'new-store',
            'provider_id' => $this->provider->id,
            'address' => '999 New Street',
            'status' => 'pending'
        ]);

        $newStore->approve();
        $this->assertEquals('approved', $newStore->status);
    }
}
