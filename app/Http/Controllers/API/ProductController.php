<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Store;
use App\Http\Resources\API\ProductResource;
use App\Http\Resources\API\ProductCategoryResource;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $categoryId = $request->get('category_id');
            $storeId = $request->get('store_id');
            $featured = $request->get('featured');
            $search = $request->get('search');
            $latitude = $request->get('latitude');
            $longitude = $request->get('longitude');

            $query = Product::with(['category', 'creator', 'variants'])
                           ->active();

            // Filter by category
            if ($categoryId) {
                $query->byCategory($categoryId);
            }

            // Filter by featured
            if ($featured) {
                $query->featured();
            }

            // Search functionality
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            }

            // If store_id is provided, get products available in that store
            if ($storeId) {
                $query->whereHas('storeProducts', function($q) use ($storeId) {
                    $q->where('store_id', $storeId)->where('is_available', true);
                });
            }

            // If location is provided, get products from nearby stores
            if ($latitude && $longitude && !$storeId) {
                $nearbyStores = Store::nearby($latitude, $longitude, 50)
                                   ->approved()
                                   ->active()
                                   ->pluck('id');

                $query->where(function($q) use ($nearbyStores) {
                    // Admin products (no store required)
                    $q->where('created_by_type', 'admin')
                      // OR products available in nearby stores
                      ->orWhereHas('storeProducts', function($sq) use ($nearbyStores) {
                          $sq->whereIn('store_id', $nearbyStores)
                             ->where('is_available', true);
                      });
                });
            }

            $products = $query->orderBy('sort_order')
                            ->orderBy('created_at', 'desc')
                            ->paginate($perPage);

            $response = [
                'status' => true,
                'data' => ProductResource::collection($products),
                'message' => __('messages.list_fetch_successfully', ['item' => __('messages.product')])
            ];

            return comman_custom_response($response);

        } catch (\Exception $e) {
            return comman_message_response(__('messages.failed'));
        }
    }

    public function show($id, Request $request)
    {
        try {
            $storeId = $request->get('store_id');
            $latitude = $request->get('latitude');
            $longitude = $request->get('longitude');

            $product = Product::with(['category', 'creator', 'provider', 'variants'])
                             ->active()
                             ->findOrFail($id);

            // In single store architecture, get the main store
            $mainStore = Store::where('store_type', 'main')->active()->first();

            // Get available stores for this product
            $availableStores = [];
            if ($mainStore) {
                $availableStores = [$mainStore];
            }

            $response = [
                'status' => true,
                'data' => new ProductResource($product),
                'available_stores' => $availableStores,
                'message' => __('messages.detail_fetch_successfully', ['item' => __('messages.product')])
            ];

            return comman_custom_response($response);

        } catch (\Exception $e) {
            return comman_message_response(__('messages.failed'));
        }
    }

    public function categories(Request $request)
    {
        try {
            $featured = $request->get('featured');

            $query = ProductCategory::active();

            if ($featured) {
                $query->featured();
            }

            $categories = $query->ordered()->get();

            $response = [
                'status' => true,
                'data' => ProductCategoryResource::collection($categories),
                'message' => __('messages.list_fetch_successfully', ['item' => __('messages.category')])
            ];

            return comman_custom_response($response);

        } catch (\Exception $e) {
            return comman_message_response(__('messages.failed'));
        }
    }

    public function search(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'query' => 'required|string|min:2',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'category_id' => 'nullable|exists:product_categories,id'
            ]);

            if ($validator->fails()) {
                return comman_message_response($validator->errors()->first());
            }

            $searchQuery = $request->get('query');
            $categoryId = $request->get('category_id');
            $latitude = $request->get('latitude');
            $longitude = $request->get('longitude');
            $perPage = $request->get('per_page', 15);

            $query = Product::with(['category', 'variants'])
                           ->active()
                           ->where(function($q) use ($searchQuery) {
                               $q->where('name', 'like', "%{$searchQuery}%")
                                 ->orWhere('description', 'like', "%{$searchQuery}%")
                                 ->orWhere('sku', 'like', "%{$searchQuery}%")
                                 ->orWhereHas('category', function($cq) use ($searchQuery) {
                                     $cq->where('name', 'like', "%{$searchQuery}%");
                                 });
                           });

            if ($categoryId) {
                $query->byCategory($categoryId);
            }

            // In single store architecture, location filtering is simplified
            // All products are available in the main store
            if ($latitude && $longitude) {
                // Could add provider location filtering here if needed
                // For now, all products are available regardless of location
            }

            $products = $query->orderBy('name')
                            ->paginate($perPage);

            $response = [
                'status' => true,
                'data' => ProductResource::collection($products),
                'message' => __('messages.list_fetch_successfully', ['item' => __('messages.product')])
            ];

            return comman_custom_response($response);

        } catch (\Exception $e) {
            return comman_message_response(__('messages.failed'));
        }
    }

    public function featured(Request $request)
    {
        try {
            $latitude = $request->get('latitude');
            $longitude = $request->get('longitude');
            $limit = $request->get('limit', 10);

            $query = Product::with(['category', 'variants'])
                           ->active()
                           ->featured();

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

            $products = $query->orderBy('sort_order')
                            ->limit($limit)
                            ->get();

            $response = [
                'status' => true,
                'data' => ProductResource::collection($products),
                'message' => __('messages.list_fetch_successfully', ['item' => __('messages.featured_product')])
            ];

            return comman_custom_response($response);

        } catch (\Exception $e) {
            return comman_message_response(__('messages.failed'));
        }
    }

    /**
     * Get products for authenticated provider (mobile-optimized)
     */
    public function providerProducts(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user || $user->user_type !== 'provider') {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $perPage = $request->get('per_page', 15);
            $categoryId = $request->get('category_id');
            $status = $request->get('status');
            $search = $request->get('search');

            $query = Product::with(['category', 'variants'])
                           ->where('created_by', $user->id)
                           ->where('created_by_type', 'provider');

            // Apply filters
            if ($categoryId) {
                $query->byCategory($categoryId);
            }

            if ($status !== null) {
                $query->where('status', $status);
            }

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            }

            $products = $query->orderBy('created_at', 'desc')
                            ->paginate($perPage);

            $response = [
                'status' => true,
                'data' => ProductResource::collection($products),
                'message' => 'Provider products retrieved successfully'
            ];

            return comman_custom_response($response);

        } catch (\Exception $e) {
            \Log::error('Provider products API error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve products'
            ], 500);
        }
    }
}
