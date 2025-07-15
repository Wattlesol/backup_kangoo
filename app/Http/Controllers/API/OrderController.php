<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ShoppingCart;
use App\Models\Store;
use App\Http\Resources\API\OrderResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $perPage = $request->get('per_page', 15);
            $status = $request->get('status');

            $query = Order::with(['store', 'items.product'])
                         ->byCustomer($user->id);

            if ($status) {
                $query->byStatus($status);
            }

            $orders = $query->orderBy('created_at', 'desc')
                          ->paginate($perPage);

            $response = [
                'status' => true,
                'data' => OrderResource::collection($orders),
                'message' => __('messages.list_fetch_successfully', ['item' => __('messages.order')])
            ];

            return comman_custom_response($response);

        } catch (\Exception $e) {
            return comman_message_response(__('messages.failed'));
        }
    }

    public function show($id)
    {
        try {
            $user = Auth::user();
            $order = Order::with(['store', 'items.product', 'items.productVariant', 'statusHistories'])
                         ->byCustomer($user->id)
                         ->findOrFail($id);

            $response = [
                'status' => true,
                'data' => new OrderResource($order),
                'message' => __('messages.detail_fetch_successfully', ['item' => __('messages.order')])
            ];

            return comman_custom_response($response);

        } catch (\Exception $e) {
            return comman_message_response(__('messages.failed'));
        }
    }

    public function create(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'delivery_address' => 'required|array',
                'delivery_address.street' => 'required|string',
                'delivery_address.city' => 'required|string',
                'delivery_address.state' => 'required|string',
                'delivery_address.country' => 'required|string',
                'delivery_address.postal_code' => 'required|string',
                'delivery_phone' => 'required|string',
                'delivery_notes' => 'nullable|string',
                'payment_method' => 'required|string',
                'store_id' => 'nullable|exists:stores,id'
            ]);

            if ($validator->fails()) {
                return comman_message_response($validator->errors()->first());
            }

            $user = Auth::user();
            
            // Get cart items
            $cartItems = ShoppingCart::byUser($user->id)->with(['product', 'productVariant', 'store'])->get();
            
            if ($cartItems->isEmpty()) {
                return comman_message_response(__('messages.cart_is_empty'));
            }

            // Group cart items by store (null for admin products)
            $groupedItems = $cartItems->groupBy('store_id');

            $orders = [];

            DB::beginTransaction();

            try {
                foreach ($groupedItems as $storeId => $items) {
                    $subtotal = $items->sum('total_price');
                    $deliveryFee = 0;
                    $orderType = 'admin';

                    if ($storeId) {
                        $store = Store::findOrFail($storeId);
                        $deliveryFee = $store->delivery_fee;
                        $orderType = 'store';
                        
                        // Check minimum order amount
                        if ($subtotal < $store->minimum_order_amount) {
                            throw new \Exception(__('messages.minimum_order_amount_not_met', ['amount' => $store->minimum_order_amount]));
                        }
                    }

                    // Create order
                    $order = Order::create([
                        'order_number' => Order::generateOrderNumber(),
                        'customer_id' => $user->id,
                        'store_id' => $storeId,
                        'order_type' => $orderType,
                        'status' => 'pending',
                        'subtotal' => $subtotal,
                        'tax_amount' => 0, // Calculate tax if needed
                        'delivery_fee' => $deliveryFee,
                        'discount_amount' => 0, // Apply discounts if needed
                        'total_amount' => $subtotal + $deliveryFee,
                        'currency' => 'USD', // Or get from settings
                        'payment_status' => 'pending',
                        'payment_method' => $request->payment_method,
                        'delivery_address' => $request->delivery_address,
                        'delivery_phone' => $request->delivery_phone,
                        'delivery_notes' => $request->delivery_notes
                    ]);

                    // Create order items
                    foreach ($items as $cartItem) {
                        OrderItem::createFromCartItem($cartItem, $order);
                        
                        // Decrease stock
                        if ($cartItem->product_variant_id) {
                            $cartItem->productVariant->decreaseStock($cartItem->quantity);
                        } else {
                            if ($cartItem->store_id) {
                                $storeProduct = $cartItem->store->storeProducts()
                                                               ->where('product_id', $cartItem->product_id)
                                                               ->first();
                                if ($storeProduct) {
                                    $storeProduct->decreaseStock($cartItem->quantity);
                                }
                            }
                            $cartItem->product->decreaseStock($cartItem->quantity);
                        }
                    }

                    // Create initial status history
                    $order->statusHistories()->create([
                        'status' => 'pending',
                        'notes' => 'Order created',
                        'changed_by' => $user->id,
                        'changed_at' => now()
                    ]);

                    $orders[] = $order;
                }

                // Clear cart
                ShoppingCart::clearCart($user->id);

                DB::commit();

                $response = [
                    'status' => true,
                    'data' => OrderResource::collection($orders),
                    'message' => __('messages.order_created_successfully')
                ];

                return comman_custom_response($response);

            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            return comman_message_response($e->getMessage());
        }
    }

    public function cancel($id, Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'reason' => 'required|string|max:500'
            ]);

            if ($validator->fails()) {
                return comman_message_response($validator->errors()->first());
            }

            $user = Auth::user();
            $order = Order::byCustomer($user->id)->findOrFail($id);

            if (!$order->can_be_cancelled) {
                return comman_message_response(__('messages.order_cannot_be_cancelled'));
            }

            $order->cancel($request->reason, $user->id);

            $response = [
                'status' => true,
                'data' => new OrderResource($order->fresh()),
                'message' => __('messages.order_cancelled_successfully')
            ];

            return comman_custom_response($response);

        } catch (\Exception $e) {
            return comman_message_response(__('messages.failed'));
        }
    }

    public function track($id)
    {
        try {
            $user = Auth::user();
            $order = Order::with(['statusHistories.changedBy'])
                         ->byCustomer($user->id)
                         ->findOrFail($id);

            $trackingData = [
                'order' => new OrderResource($order),
                'status_history' => $order->statusHistories->map(function($history) {
                    return [
                        'status' => $history->status,
                        'status_label' => $history->status_label,
                        'notes' => $history->notes,
                        'changed_by' => $history->changed_by_name,
                        'changed_at' => $history->changed_at
                    ];
                })
            ];

            $response = [
                'status' => true,
                'data' => $trackingData,
                'message' => __('messages.order_tracking_fetched')
            ];

            return comman_custom_response($response);

        } catch (\Exception $e) {
            return comman_message_response(__('messages.failed'));
        }
    }
}
