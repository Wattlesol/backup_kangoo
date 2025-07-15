<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Store;
use App\Models\ShoppingCart;

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
        $product = Product::with(['category', 'creator', 'variants', 'storeProducts.store'])
                         ->where('slug', $slug)
                         ->active()
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
     */
    public function getProducts(Request $request)
    {
        $perPage = $request->get('per_page', 12);
        $categoryId = $request->get('category_id');
        $search = $request->get('search');
        $latitude = $request->get('latitude');
        $longitude = $request->get('longitude');
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $query = Product::with(['category', 'creator', 'variants'])
                       ->active();

        // Filter by category
        if ($categoryId) {
            $query->byCategory($categoryId);
        }

        // Search functionality
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Location-based filtering
        if ($latitude && $longitude) {
            $nearbyStores = Store::nearby($latitude, $longitude, 50)
                               ->approved()
                               ->active()
                               ->pluck('id');

            $query->where(function($q) use ($nearbyStores) {
                $q->where('created_by_type', 'admin')
                  ->orWhereHas('storeProducts', function($sq) use ($nearbyStores) {
                      $sq->whereIn('store_id', $nearbyStores)
                         ->where('is_available', true);
                  });
            });
        }

        // Sorting
        switch ($sortBy) {
            case 'name':
                $query->orderBy('name', $sortOrder);
                break;
            case 'price':
                $query->orderBy('base_price', $sortOrder);
                break;
            case 'created_at':
            default:
                $query->orderBy('created_at', $sortOrder);
                break;
        }

        $products = $query->paginate($perPage);

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
}
