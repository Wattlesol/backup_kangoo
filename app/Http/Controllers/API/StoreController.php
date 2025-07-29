<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Store;
use App\Models\Product;
use App\Http\Resources\API\StoreResource;
use App\Http\Resources\API\ProductResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class StoreController extends Controller
{
    public function index(Request $request)
    {
        try {
            // In single store architecture, return the main store
            $mainStore = Store::with(['createdBy', 'country', 'state', 'city'])
                             ->where('store_type', 'main')
                             ->active()
                             ->first();

            if (!$mainStore) {
                return comman_message_response(__('messages.store_not_found'));
            }

            $response = [
                'status' => true,
                'data' => new StoreResource($mainStore),
                'message' => __('messages.detail_fetch_successfully', ['item' => __('messages.store')])
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
            $store = Store::where('store_type', 'main')->active()->findOrFail($storeId);

            $perPage = $request->get('per_page', 15);
            $categoryId = $request->get('category_id');
            $search = $request->get('search');
            $providerId = $request->get('provider_id');

            // In single store architecture, get all approved and available products
            $query = Product::with(['category', 'provider', 'variants'])
                           ->where('is_available', true)
                           ->where('status', true)
                           ->where('approval_status', 'approved');

            // Filter by category
            if ($categoryId) {
                $query->where('product_category_id', $categoryId);
            }

            // Filter by provider
            if ($providerId) {
                $query->where('provider_id', $providerId);
            }

            // Search functionality
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            }

            $products = $query->paginate($perPage);

            // Return products with single store architecture
            $productCollection = ProductResource::collection($products);

            $response = [
                'status' => true,
                'data' => $productCollection,
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
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
