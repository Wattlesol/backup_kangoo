<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Http\Resources\API\StoreResource;
use App\Http\Resources\API\ProductResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class StoreController extends Controller
{
    public function index(Request $request)
    {
        try {
            $latitude = $request->get('latitude');
            $longitude = $request->get('longitude');
            $radius = $request->get('radius', 50);
            $perPage = $request->get('per_page', 15);

            $query = Store::with(['provider', 'country', 'state', 'city'])
                         ->approved()
                         ->active();

            // Location-based filtering
            if ($latitude && $longitude) {
                $query = $query->nearby($latitude, $longitude, $radius);
            }

            $stores = $query->paginate($perPage);

            $response = [
                'status' => true,
                'data' => StoreResource::collection($stores),
                'message' => __('messages.list_fetch_successfully', ['item' => __('messages.store')])
            ];

            return comman_custom_response($response);

        } catch (\Exception $e) {
            return comman_message_response(__('messages.failed'));
        }
    }

    public function show($id, Request $request)
    {
        try {
            $store = Store::with(['provider', 'country', 'state', 'city'])
                         ->approved()
                         ->active()
                         ->findOrFail($id);

            $response = [
                'status' => true,
                'data' => new StoreResource($store),
                'message' => __('messages.detail_fetch_successfully', ['item' => __('messages.store')])
            ];

            return comman_custom_response($response);

        } catch (\Exception $e) {
            return comman_message_response(__('messages.failed'));
        }
    }

    public function products($storeId, Request $request)
    {
        try {
            $store = Store::approved()->active()->findOrFail($storeId);
            
            $perPage = $request->get('per_page', 15);
            $categoryId = $request->get('category_id');
            $search = $request->get('search');

            $query = StoreProduct::with(['product.category', 'product.variants'])
                                ->where('store_id', $storeId)
                                ->where('is_available', true)
                                ->whereHas('product', function($q) {
                                    $q->active();
                                });

            // Filter by category
            if ($categoryId) {
                $query->whereHas('product', function($q) use ($categoryId) {
                    $q->where('product_category_id', $categoryId);
                });
            }

            // Search functionality
            if ($search) {
                $query->whereHas('product', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            }

            $storeProducts = $query->paginate($perPage);

            // Transform to product resources with store-specific data
            $products = $storeProducts->getCollection()->map(function($storeProduct) {
                $product = $storeProduct->product;
                $product->store_price = $storeProduct->store_price;
                $product->store_stock = $storeProduct->stock_quantity;
                $product->final_price = $storeProduct->final_price;
                return new ProductResource($product);
            });

            $response = [
                'status' => true,
                'data' => $products,
                'pagination' => [
                    'current_page' => $storeProducts->currentPage(),
                    'last_page' => $storeProducts->lastPage(),
                    'per_page' => $storeProducts->perPage(),
                    'total' => $storeProducts->total(),
                ],
                'store' => new StoreResource($store),
                'message' => __('messages.list_fetch_successfully', ['item' => __('messages.product')])
            ];

            return comman_custom_response($response);

        } catch (\Exception $e) {
            return comman_message_response(__('messages.failed'));
        }
    }

    public function nearby(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
                'radius' => 'nullable|numeric|min:1|max:100'
            ]);

            if ($validator->fails()) {
                return comman_message_response($validator->errors()->first());
            }

            $latitude = $request->get('latitude');
            $longitude = $request->get('longitude');
            $radius = $request->get('radius', 50);
            $limit = $request->get('limit', 20);

            $stores = Store::with(['provider'])
                          ->approved()
                          ->active()
                          ->nearby($latitude, $longitude, $radius)
                          ->limit($limit)
                          ->get();

            $response = [
                'status' => true,
                'data' => StoreResource::collection($stores),
                'message' => __('messages.list_fetch_successfully', ['item' => __('messages.nearby_store')])
            ];

            return comman_custom_response($response);

        } catch (\Exception $e) {
            return comman_message_response(__('messages.failed'));
        }
    }

    // Provider endpoints
    public function myStore(Request $request)
    {
        try {
            $user = Auth::user();
            
            if ($user->user_type !== 'provider') {
                return comman_message_response(__('messages.unauthorized'));
            }

            $store = Store::with(['country', 'state', 'city'])
                         ->where('provider_id', $user->id)
                         ->first();

            if (!$store) {
                return comman_message_response(__('messages.store_not_found'));
            }

            $response = [
                'status' => true,
                'data' => new StoreResource($store),
                'message' => __('messages.detail_fetch_successfully', ['item' => __('messages.store')])
            ];

            return comman_custom_response($response);

        } catch (\Exception $e) {
            return comman_message_response(__('messages.failed'));
        }
    }

    public function createStore(Request $request)
    {
        try {
            $user = Auth::user();
            
            if ($user->user_type !== 'provider') {
                return comman_message_response(__('messages.unauthorized'));
            }

            // Check if provider already has a store
            $existingStore = Store::where('provider_id', $user->id)->first();
            if ($existingStore) {
                return comman_message_response(__('messages.store_already_exists'));
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'phone' => 'nullable|string|max:20',
                'address' => 'required|string',
                'country_id' => 'nullable|exists:countries,id',
                'state_id' => 'nullable|exists:states,id',
                'city_id' => 'nullable|exists:cities,id',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'business_hours' => 'nullable|array',
                'delivery_radius' => 'nullable|numeric|min:0',
                'minimum_order_amount' => 'nullable|numeric|min:0',
                'delivery_fee' => 'nullable|numeric|min:0'
            ]);

            if ($validator->fails()) {
                return comman_message_response($validator->errors()->first());
            }

            $storeData = $request->only([
                'name', 'description', 'phone', 'address', 'country_id', 
                'state_id', 'city_id', 'latitude', 'longitude', 'business_hours',
                'delivery_radius', 'minimum_order_amount', 'delivery_fee'
            ]);

            $storeData['provider_id'] = $user->id;
            $storeData['slug'] = \Str::slug($storeData['name'] . '-' . $user->id);
            $storeData['status'] = 'pending';

            $store = Store::create($storeData);

            $response = [
                'status' => true,
                'data' => new StoreResource($store),
                'message' => __('messages.store_created_pending_approval')
            ];

            return comman_custom_response($response);

        } catch (\Exception $e) {
            return comman_message_response(__('messages.failed'));
        }
    }

    public function updateStore(Request $request)
    {
        try {
            $user = Auth::user();
            
            if ($user->user_type !== 'provider') {
                return comman_message_response(__('messages.unauthorized'));
            }

            $store = Store::where('provider_id', $user->id)->first();
            
            if (!$store) {
                return comman_message_response(__('messages.store_not_found'));
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'phone' => 'nullable|string|max:20',
                'address' => 'sometimes|required|string',
                'country_id' => 'nullable|exists:countries,id',
                'state_id' => 'nullable|exists:states,id',
                'city_id' => 'nullable|exists:cities,id',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'business_hours' => 'nullable|array',
                'delivery_radius' => 'nullable|numeric|min:0',
                'minimum_order_amount' => 'nullable|numeric|min:0',
                'delivery_fee' => 'nullable|numeric|min:0'
            ]);

            if ($validator->fails()) {
                return comman_message_response($validator->errors()->first());
            }

            $updateData = $request->only([
                'name', 'description', 'phone', 'address', 'country_id', 
                'state_id', 'city_id', 'latitude', 'longitude', 'business_hours',
                'delivery_radius', 'minimum_order_amount', 'delivery_fee'
            ]);

            if (isset($updateData['name'])) {
                $updateData['slug'] = \Str::slug($updateData['name'] . '-' . $user->id);
            }

            $store->update($updateData);

            $response = [
                'status' => true,
                'data' => new StoreResource($store->fresh()),
                'message' => __('messages.store_updated_successfully')
            ];

            return comman_custom_response($response);

        } catch (\Exception $e) {
            return comman_message_response(__('messages.failed'));
        }
    }
}
