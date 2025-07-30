<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Store;
use App\Models\ShoppingCart;
use App\Models\User;

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

        $categories = ProductCategory::withCount('products')->active()->ordered()->get();
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
     * Display shopping cart
     */
    public function cart()
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login')->with('message', 'Please login to view your cart');
        }

        $cartSummary = ShoppingCart::getCartSummary($user->id);
        $pageTitle = 'Shopping Cart';

        return view('landing-page.products.cart', compact('cartSummary', 'pageTitle'));
    }

    /**
     * Display checkout page
     */
    public function checkout()
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login')->with('message', 'Please login to checkout');
        }

        $cartSummary = ShoppingCart::getCartSummary($user->id);

        if ($cartSummary['items']->isEmpty()) {
            return redirect()->route('products.cart')->with('error', 'Your cart is empty');
        }

        $pageTitle = 'Checkout';

        return view('landing-page.products.checkout', compact('cartSummary', 'pageTitle'));
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

        $pageTitle = 'Order Success';

        return view('landing-page.products.order-success', compact('orders', 'pageTitle'));
    }

    /**
     * Display stores listing
     */
    public function stores(Request $request)
    {
        $latitude = $request->get('latitude');
        $longitude = $request->get('longitude');

        $pageTitle = 'Stores';

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

        // Transform products for frontend
        $products->getCollection()->transform(function ($product) {
            $product->price_formatted = getPriceFormat($product->selling_price ?: $product->base_price);
            $product->category_name = $product->category ? $product->category->name : '';
            $product->provider_name = $product->provider ? $product->provider->display_name : '';
            $product->image_url = $product->getFirstMediaUrl('product_images') ?: null;
            $product->url = route('products.show', $product->slug);
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
        $perPage = $request->get('per_page', 12);
        $latitude = $request->get('latitude');
        $longitude = $request->get('longitude');
        $radius = $request->get('radius', 50);

        $query = Store::with(['provider'])
                     ->approved()
                     ->active();

        // Location-based filtering
        if ($latitude && $longitude) {
            $query = $query->nearby($latitude, $longitude, $radius);
        }

        $stores = $query->paginate($perPage);

        return response()->json([
            'status' => true,
            'data' => $stores,
            'message' => 'Stores fetched successfully'
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
                'pageTitle' => 'Store Unavailable',
                'message' => 'The store is currently unavailable. Please check back later.'
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
}
