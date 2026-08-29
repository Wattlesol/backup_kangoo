<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Store;

use App\Models\User;
use App\Models\Order;

class ProductController extends Controller
{
    /**
     * Display store index page with products, stores, and categories
     */
    public function index(Request $request)
    {
        $view = $request->get('view', 'products');
        $categoryId = $request->get('category');
        $search = $request->get('q');
        $location = $request->get('location');
        $sort = $request->get('sort', 'name');

        $categories = ProductCategory::withCount('products')->active()->ordered()->paginate(12);
        $featuredCategories = ProductCategory::withCount('products')->active()->featured()->ordered()->get();

        // Get unique locations from stores
        $locations = collect(); // Disabled for now since stores don't show on frontend

        $pageTitle = __('landingpage.store');

        if ($view === 'categories') {
            return view('landing-page.store.categories', compact(
                'categories',
                'featuredCategories',
                'pageTitle'
            ));
        }

        return view('landing-page.store.index', compact(
            'categories',
            'featuredCategories',
            'locations',
            'pageTitle',
            'search',
            'location',
            'sort'
        ));
    }

    /**
     * Display products listing page
     */
    public function products(Request $request)
    {
        $categoryId = $request->get('category');
        $search = $request->get('search');
        $latitude = $request->get('latitude');
        $longitude = $request->get('longitude');

        $categories = ProductCategory::active()->ordered()->get();
        $selectedCategory = $categoryId ? ProductCategory::find($categoryId) : null;

        $pageTitle = $selectedCategory ? $selectedCategory->name : 'Products';

        return view('landing-page.products.index', compact(
            'categories',
            'selectedCategory',
            'pageTitle',
            'search',
            'latitude',
            'longitude'
        ));
    }



    /**
     * Display single product page
     */
    public function show($slug, Request $request)
    {
        // Temporarily remove active() scope to debug
        $product = Product::with(['category', 'variants'])
                         ->where('slug', $slug)
                         ->where('status', true)
                         ->where('is_available', true)
                         ->where('approval_status', 'approved')
                         ->firstOrFail();

        $latitude = $request->get('latitude');
        $longitude = $request->get('longitude');

        // Get available stores for this product
        $availableStores = collect();

        if ($latitude && $longitude) {
            $availableStores = Store::nearby($latitude, $longitude, 50)
                                   ->approved()
                                   ->active()
                                   ->whereHas('storeProducts', function($q) use ($product) {
                                       $q->where('product_id', $product->id)
                                         ->where('is_available', true);
                                   })
                                   ->with(['storeProducts' => function($q) use ($product) {
                                       $q->where('product_id', $product->id);
                                   }])
                                   ->get();
        }

        // Get related products
        $relatedProducts = Product::with(['category'])
                                 ->where('product_category_id', $product->product_category_id)
                                 ->where('id', '!=', $product->id)
                                 ->active()
                                 ->limit(8)
                                 ->get();

        $pageTitle = $product->name;

        return view('landing-page.products.show', compact(
            'product',
            'availableStores',
            'relatedProducts',
            'pageTitle',
            'latitude',
            'longitude'
        ));
    }

    /**
     * Display products by category
     */
    public function category($slug, Request $request)
    {
        $category = ProductCategory::where('slug', $slug)->active()->firstOrFail();
        $latitude = $request->get('latitude');
        $longitude = $request->get('longitude');

        $pageTitle = $category->name;

        return view('landing-page.products.category', compact(
            'category',
            'pageTitle',
            'latitude',
            'longitude'
        ));
    }

    /**
     * Search products and stores
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        $categoryId = $request->get('category');
        $location = $request->get('location');
        $sort = $request->get('sort', 'name');
        $latitude = $request->get('latitude');
        $longitude = $request->get('longitude');

        $categories = ProductCategory::withCount('products')->active()->ordered()->get();
        $selectedCategory = $categoryId ? ProductCategory::find($categoryId) : null;

        // Get unique locations from stores
        $locations = collect(); // Disabled for now since stores don't show on frontend

        $pageTitle = __('landingpage.search_results') . ($query ? ' for "' . $query . '"' : '');

        return view('landing-page.store.search', compact(
            'query',
            'categories',
            'selectedCategory',
            'locations',
            'pageTitle',
            'location',
            'sort',
            'latitude',
            'longitude'
        ));
    }



    /**
     * Display checkout page for direct product purchase
     */
    public function checkout(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login')->with('message', 'Please login to checkout');
        }

        // Ensure only customers can access checkout
        if ($user->user_type !== 'user') {
            abort(403, 'Access denied. Only customers can access checkout.');
        }

        // Get product information from request parameters
        $productId = $request->get('product_id');
        $quantity = $request->get('quantity', 1);

        if (!$productId) {
            return redirect()->route('store')->with('error', 'Please select a product to purchase');
        }

        $product = Product::with(['store', 'category'])->findOrFail($productId);

        $pageTitle = (app()->getLocale() === 'ar' ? 'إتمام الطلب - ' : 'Checkout - ') . $product->name;

        return view('landing-page.products.checkout', compact('product', 'quantity', 'pageTitle'));
    }

    /**
     * Store a new order from direct product purchase
     */
    public function storeOrder(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login')->with('message', 'Please login to place an order');
        }

        // Ensure only customers can place orders
        if ($user->user_type !== 'user') {
            abort(403, 'Access denied. Only customers can place orders.');
        }

        // Validate the request
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'zip' => 'required|string|max:20',
            'payment_method' => 'required|in:cash,online',
        ]);

        try {
            $product = Product::findOrFail($request->product_id);
            $quantity = $request->quantity;

            // Calculate totals
            $subtotal = $product->effective_price * $quantity;
            $tax = $subtotal * 0.10; // 10% tax
            $deliveryFee = 5.00;
            $total = $subtotal + $tax + $deliveryFee;

            // Create the order
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'customer_id' => $user->id,
                'store_id' => null, // Admin order (unified store)
                'order_type' => 'admin',
                'status' => 'pending',
                'payment_status' => $request->payment_method === 'cash' ? 'pending' : 'pending',
                'payment_method' => $request->payment_method,
                'total_amount' => $total,
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'delivery_fee' => $deliveryFee,
                'currency' => 'SAR', // Saudi Riyal
                'delivery_address' => [
                    'name' => $request->first_name . ' ' . $request->last_name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'address' => $request->address,
                    'city' => $request->city,
                    'state' => $request->state,
                    'zip' => $request->zip,
                ],
                'delivery_phone' => $request->phone,
                'delivery_notes' => $request->delivery_notes,
            ]);

            // Create order item
            $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku ?? 'N/A',
                'product_details' => [
                    'name' => $product->name,
                    'description' => $product->description,
                    'category' => $product->category->name ?? null,
                    'image' => $product->getFirstMediaUrl('product_images')
                ],
                'quantity' => $quantity,
                'unit_price' => $product->effective_price,
                'total_price' => $product->effective_price * $quantity,
            ]);

            // Redirect to success page
            return redirect()->route('products.order-success', ['orders' => $order->id])
                           ->with('success', 'Order placed successfully!');

        } catch (\Exception $e) {
            \Log::error('Order creation failed: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Failed to place order. Please try again.');
        }
    }

    /**
     * Display a specific order for the customer
     */
    public function showOrder($id)
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login')->with('message', 'Please login to view your orders');
        }

        // Ensure only customers can view orders
        if ($user->user_type !== 'user') {
            abort(403, 'Access denied. Only customers can view orders.');
        }

        // Get the order for the current user only
        $order = Order::with(['items.product', 'items.product.category', 'store'])
                     ->where('customer_id', $user->id)
                     ->findOrFail($id);

        $pageTitle = (app()->getLocale() === 'ar' ? 'تفاصيل الطلب - #' : 'Order Details - #') . $order->order_number;

        return view('landing-page.products.order-detail', compact('order', 'pageTitle'));
    }

    /**
     * Cancel a customer order
     */
    public function cancelOrder($id, Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Please login to cancel orders'], 401);
        }

        // Ensure only customers can cancel orders
        if ($user->user_type !== 'user') {
            return response()->json(['success' => false, 'message' => 'Access denied. Only customers can cancel orders.'], 403);
        }

        try {
            // Get the order for the current user only
            $order = Order::where('customer_id', $user->id)->findOrFail($id);

            // Check if order can be cancelled
            if (!$order->can_be_cancelled) {
                return response()->json([
                    'success' => false,
                    'message' => 'This order cannot be cancelled. Only pending or confirmed orders can be cancelled.'
                ], 400);
            }

            // Get cancellation reason from request or use default
            $reason = $request->input('reason', 'Cancelled by customer');

            // Cancel the order using the model method
            $order->cancel($reason, $user->id);

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel order. Please try again.'
            ], 500);
        }
    }

    /**
     * Display order success page
     */
    public function orderSuccess(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login')->with('message', 'Please login to view your orders');
        }

        // Get order IDs from query parameter
        $orderIds = $request->get('orders');
        if (!$orderIds) {
            return redirect()->route('frontend.index')->with('error', 'No orders found');
        }

        // Convert comma-separated string to array
        $orderIds = explode(',', $orderIds);

        // Get orders for the current user
        $orders = Order::with(['items', 'store'])
                      ->where('customer_id', $user->id)
                      ->whereIn('id', $orderIds)
                      ->get();

        if ($orders->isEmpty()) {
            return redirect()->route('frontend.index')->with('error', 'Orders not found');
        }

        $pageTitle = app()->getLocale() === 'ar' ? 'تم استلام طلبك بنجاح' : 'Order Success';

        return view('landing-page.products.order-success', compact('orders', 'pageTitle'));
    }

    /**
     * Display payment failed page
     */
    public function paymentFailed(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login')->with('message', 'Please login to view your orders');
        }

        // Get order ID from query parameter if provided
        $orderId = $request->get('order_id');
        $order = null;

        if ($orderId) {
            $order = Order::with(['items', 'store'])
                          ->where('customer_id', $user->id)
                          ->where('id', $orderId)
                          ->first();
        }

        $pageTitle = app()->getLocale() === 'ar' ? 'فشلت عملية الدفع' : 'Payment Failed';

        return view('landing-page.products.payment-failed', compact('order', 'pageTitle'));
    }

    /**
     * Display stores listing
     */
    public function stores(Request $request)
    {
        $latitude = $request->get('latitude');
        $longitude = $request->get('longitude');

        $pageTitle = __('messages.store');

        return view('landing-page.stores.index', compact('pageTitle', 'latitude', 'longitude'));
    }

    /**
     * Display single store page
     */
    public function storeShow($slug, Request $request)
    {
        $store = Store::with(['provider', 'country', 'state', 'city'])
                     ->where('slug', $slug)
                     ->approved()
                     ->active()
                     ->firstOrFail();

        $pageTitle = $store->name;

        return view('landing-page.stores.show', compact('store', 'pageTitle'));
    }

    /**
     * Get product data for AJAX requests
     * Updated for single-store architecture
     */
    public function getProducts(Request $request)
    {
        $perPage = $request->get('per_page', 12);
        $categoryId = $request->get('category_id');
        $search = $request->get('search');
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $priceMin = $request->get('price_min');
        $priceMax = $request->get('price_max');
        $inStockOnly = $request->get('in_stock_only', false);
        $featuredOnly = $request->get('featured_only', false);

        // Only show available and approved products
        // Provider information hidden for unified customer experience
        $query = Product::with(['category', 'variants'])
                       ->where('is_available', true)
                       ->where('status', true)
                       ->where('approval_status', 'approved');

        // Filter by category
        if ($categoryId) {
            $query->where('product_category_id', $categoryId);
        }

        // Search functionality
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('short_description', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Price range filtering
        if ($priceMin) {
            $query->whereRaw('COALESCE(selling_price, base_price) >= ?', [$priceMin]);
        }
        if ($priceMax) {
            $query->whereRaw('COALESCE(selling_price, base_price) <= ?', [$priceMax]);
        }

        // Provider filtering removed for unified customer experience

        // Stock filtering
        if ($inStockOnly) {
            $query->where('stock_quantity', '>', 0);
        }

        // Featured products only
        if ($featuredOnly) {
            $query->where('is_featured', true);
        }

        // Sorting
        switch ($sortBy) {
            case 'name':
                $query->orderBy('name', $sortOrder);
                break;
            case 'price':
                $query->orderByRaw('COALESCE(selling_price, base_price) ' . $sortOrder);
                break;
            case 'created_at':
                $query->orderBy('created_at', $sortOrder);
                break;
            case 'popularity':
                // Order by featured first, then by created date
                $query->orderBy('is_featured', 'desc')->orderBy('created_at', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $products = $query->paginate($perPage);

        // Transform products for customer-friendly frontend display
        $products->getCollection()->transform(function ($product) {
            // Customer-focused pricing information
            $product->price_formatted = getPriceFormat($product->selling_price ?: $product->base_price);
            $product->original_price = $product->base_price;
            $product->sale_price = $product->selling_price;
            $product->has_discount = $product->selling_price && $product->selling_price < $product->base_price;
            $product->discount_percentage = $product->has_discount ?
                round((($product->base_price - $product->selling_price) / $product->base_price) * 100) : 0;

            // Customer-relevant product information
            $product->category_name = $product->category ? $product->category->name : '';
            $product->main_image = $product->getFirstMediaUrl('product_images') ?: asset('images/default-product.jpg');
            $product->url = route('products.show', $product->slug);

            // Stock and availability (customer-friendly)
            $product->is_in_stock = $product->stock_quantity > 0;
            $product->is_low_stock = $product->stock_quantity <= ($product->low_stock_threshold ?? 5);
            $product->stock_status = $product->is_in_stock ?
                ($product->is_low_stock ? 'Low Stock' : 'In Stock') : 'Out of Stock';

            // Customer benefits and features
            $product->key_features = $this->extractKeyFeatures($product);
            $product->customer_benefits = $this->getCustomerBenefits($product);

            // Remove technical/administrative fields that customers don't need
            unset($product->sku, $product->created_by, $product->created_by_type,
                  $product->provider_id, $product->approval_status, $product->approved_at,
                  $product->approved_by, $product->admin_notes, $product->provider_notes,
                  $product->meta_data, $product->sort_order, $product->track_inventory,
                  $product->minimum_order_quantity, $product->maximum_order_quantity,
                  $product->low_stock_threshold);

            return $product;
        });

        return response()->json([
            'status' => true,
            'data' => $products,
            'message' => 'Products fetched successfully'
        ]);
    }

    /**
     * Get stores data for AJAX requests
     */
    public function getStores(Request $request)
    {
        // In single-store architecture, return the main store only
        $mainStore = Store::with(['country', 'state', 'city'])
                         ->where('store_type', 'main')
                         ->approved()
                         ->active()
                         ->first();

        if (!$mainStore) {
            return response()->json([
                'status' => false,
                'data' => null,
                'message' => 'Main store not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $mainStore,
            'message' => 'Store fetched successfully'
        ]);
    }

    /**
     * Display unified store page with all products from all providers
     * Updated for single-store architecture
     */
    public function unifiedStore(Request $request)
    {
        // Get the main store
        $mainStore = Store::where('store_type', 'main')->first();

        if (!$mainStore || !$mainStore->is_active) {
            return view('landing-page.store.unavailable', [
                'pageTitle' => app()->getLocale() === 'ar' ? 'المتجر غير متاح' : 'Store Unavailable',
                'message' => app()->getLocale() === 'ar' ? 'المتجر غير متاح حالياً. يرجى التحقق مرة أخرى لاحقاً.' : 'The store is currently unavailable. Please check back later.'
            ]);
        }

        $categoryId = $request->get('category');
        $search = $request->get('q');
        $sort = $request->get('sort', 'name');
        $priceMin = $request->get('price_min');
        $priceMax = $request->get('price_max');
        $inStockOnly = $request->get('in_stock_only', false);
        $featuredOnly = $request->get('featured_only', false);

        // Get all categories with product counts (only for available products)
        $categories = ProductCategory::withCount(['products' => function($q) {
            $q->where('is_available', true)->where('status', true)->where('approval_status', 'approved');
        }])->active()->ordered()->get();

        $featuredCategories = ProductCategory::withCount(['products' => function($q) {
            $q->where('is_available', true)->where('status', true)->where('approval_status', 'approved')->where('is_featured', true);
        }])->active()->featured()->ordered()->get();

        // Providers are hidden from customers for unified store experience
        $providers = collect(); // Empty collection - no provider visibility for customers

        // Get price range for filtering (from available and approved products only)
        $priceRange = Product::where('is_available', true)
                            ->where('status', true)
                            ->where('approval_status', 'approved')
                            ->selectRaw('MIN(COALESCE(selling_price, base_price)) as min_price, MAX(COALESCE(selling_price, base_price)) as max_price')
                            ->first();

        $pageTitle = $mainStore->name;

        return view('landing-page.store.unified', compact(
            'mainStore',
            'categories',
            'featuredCategories',
            'providers',
            'priceRange',
            'pageTitle',
            'categoryId',
            'search',
            'sort',
            'priceMin',
            'priceMax',
            'inStockOnly',
            'featuredOnly'
        ));
    }

    /**
     * Extract key features from product for customer display
     */
    private function extractKeyFeatures($product)
    {
        $features = [];

        // Add weight if available (useful for shipping)
        if ($product->weight) {
            $features[] = "Weight: {$product->weight} kg";
        }

        // Add dimensions if available
        if ($product->dimensions) {
            if (is_array($product->dimensions)) {
                $features[] = "Dimensions: " . implode(' x ', $product->dimensions) . " cm";
            } else {
                $features[] = "Dimensions: {$product->dimensions}";
            }
        }

        // Add category as a feature
        if ($product->category) {
            $features[] = "Category: {$product->category->name}";
        }

        // Add featured status
        if ($product->is_featured) {
            $features[] = "Featured Product";
        }

        return $features;
    }

    /**
     * Get customer benefits and selling points
     */
    private function getCustomerBenefits($product)
    {
        $benefits = [
            'Fast Delivery Available',
            'Secure Payment Options',
            'Quality Guaranteed'
        ];

        // Add stock-based benefits
        if ($product->stock_quantity > 0) {
            $benefits[] = 'Ready to Ship';
        }

        // Add discount benefit
        if ($product->selling_price && $product->selling_price < $product->base_price) {
            $benefits[] = 'Special Offer Price';
        }

        // Add featured benefit
        if ($product->is_featured) {
            $benefits[] = 'Top Rated Product';
        }

        return $benefits;
    }
}
