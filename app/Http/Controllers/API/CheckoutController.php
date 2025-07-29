<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\ShoppingCart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\User;
use App\Models\Store;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StoreProduct;

class CheckoutController extends Controller
{
    /**
     * Get checkout summary
     */
    public function summary(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Validate cart first
            $errors = ShoppingCart::validateCart($user->id);
            if (!empty($errors)) {
                return comman_custom_response([
                    'status' => false,
                    'data' => ['errors' => $errors],
                    'message' => 'Cart validation failed'
                ]);
            }

            // Get cart totals
            $deliveryAddress = $request->get('delivery_address');
            $totals = ShoppingCart::calculateTotals($user->id, $deliveryAddress);

            // Get cart items grouped by store
            $cartByStores = ShoppingCart::getCartByStores($user->id);

            $response = [
                'status' => true,
                'data' => [
                    'totals' => $totals,
                    'cart_by_stores' => $cartByStores,
                    'customer' => [
                        'id' => $user->id,
                        'name' => $user->display_name,
                        'email' => $user->email,
                        'phone' => $user->contact_number
                    ]
                ],
                'message' => 'Checkout summary fetched successfully'
            ];

            return comman_custom_response($response);

        } catch (\Exception $e) {
            return comman_message_response(__('messages.failed'));
        }
    }

    /**
     * Process checkout and create order
     */
    public function process(Request $request)
    {
        try {
            $user = Auth::user();

            // Validate request
            $validator = Validator::make($request->all(), [
                'delivery_address' => 'required|array',
                'delivery_address.name' => 'required|string|max:255',
                'delivery_address.address' => 'required|string|max:500',
                'delivery_address.city' => 'required|string|max:100',
                'delivery_address.state' => 'required|string|max:100',
                'delivery_address.zip' => 'required|string|max:20',
                'delivery_address.country' => 'required|string|max:100',
                'delivery_phone' => 'required|string|max:20',
                'delivery_notes' => 'nullable|string|max:500',
                'payment_method' => 'required|string|in:cash,card,wallet',
            ]);

            if ($validator->fails()) {
                return comman_custom_response([
                    'status' => false,
                    'data' => ['errors' => $validator->errors()],
                    'message' => 'Validation failed'
                ]);
            }

            // Validate cart
            $errors = ShoppingCart::validateCart($user->id);
            if (!empty($errors)) {
                return comman_custom_response([
                    'status' => false,
                    'data' => ['errors' => $errors],
                    'message' => 'Cart validation failed'
                ]);
            }

            // Get cart items
            $cartItems = ShoppingCart::byUser($user->id)->with(['product', 'productVariant', 'store'])->get();
            
            if ($cartItems->isEmpty()) {
                return comman_message_response('Cart is empty');
            }

            // Calculate totals
            $totals = ShoppingCart::calculateTotals($user->id, $request->delivery_address);

            DB::beginTransaction();

            try {
                // Group cart items by store (including admin products as null store)
                $itemsByStore = $cartItems->groupBy('store_id');

                $orders = [];

                foreach ($itemsByStore as $storeId => $items) {
                    // Calculate subtotal for this store
                    $storeSubtotal = $items->sum('total_price');
                    
                    // For simplicity, distribute tax and delivery proportionally
                    $proportion = $storeSubtotal / $totals['subtotal'];
                    $storeTax = $totals['tax_amount'] * $proportion;
                    $storeDelivery = $totals['delivery_fee'] * $proportion;
                    $storeDiscount = $totals['discount_amount'] * $proportion;
                    $storeTotal = $storeSubtotal + $storeTax + $storeDelivery - $storeDiscount;

                    // Create order
                    $order = Order::create([
                        'order_number' => $this->generateOrderNumber(),
                        'customer_id' => $user->id,
                        'store_id' => $storeId,
                        'order_type' => $storeId ? 'store' : 'admin',
                        'status' => 'pending',
                        'subtotal' => $storeSubtotal,
                        'tax_amount' => $storeTax,
                        'delivery_fee' => $storeDelivery,
                        'discount_amount' => $storeDiscount,
                        'total_amount' => $storeTotal,
                        'currency' => 'USD', // You can make this configurable
                        'payment_status' => 'pending',
                        'payment_method' => $request->payment_method,
                        'delivery_address' => $request->delivery_address,
                        'delivery_phone' => $request->delivery_phone,
                        'delivery_notes' => $request->delivery_notes,
                    ]);

                    // Create order items
                    foreach ($items as $cartItem) {
                        OrderItem::create([
                            'order_id' => $order->id,
                            'product_id' => $cartItem->product_id,
                            'product_variant_id' => $cartItem->product_variant_id,
                            'product_name' => $cartItem->product->name,
                            'product_sku' => $cartItem->product->sku,
                            'product_variant_name' => $cartItem->productVariant ? $cartItem->productVariant->attribute_display : null,
                            'quantity' => $cartItem->quantity,
                            'unit_price' => $cartItem->unit_price,
                            'total_price' => $cartItem->total_price,
                        ]);

                        // Reduce stock
                        if ($cartItem->product_variant_id) {
                            $cartItem->productVariant->decreaseStock($cartItem->quantity);
                        } else {
                            if ($cartItem->store_id) {
                                $storeProduct = StoreProduct::where('store_id', $cartItem->store_id)
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
                    OrderStatusHistory::create([
                        'order_id' => $order->id,
                        'status' => 'pending',
                        'notes' => 'Order created',
                        'changed_by' => $user->id,
                    ]);

                    $orders[] = $order;
                }

                // Clear cart
                ShoppingCart::clearCart($user->id);

                DB::commit();

                $response = [
                    'status' => true,
                    'data' => [
                        'orders' => $orders,
                        'total_orders' => count($orders),
                        'total_amount' => $totals['total_amount']
                    ],
                    'message' => 'Order placed successfully'
                ];

                return comman_custom_response($response);

            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            return comman_message_response(__('messages.failed'));
        }
    }

    /**
     * Generate unique order number
     */
    private function generateOrderNumber()
    {
        $prefix = 'ORD-';
        $date = date('Ymd');
        
        // Get the last order number for today
        $lastOrder = Order::where('order_number', 'like', $prefix . $date . '%')
                          ->orderBy('order_number', 'desc')
                          ->first();

        if ($lastOrder) {
            $lastNumber = intval(substr($lastOrder->order_number, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $date . '-' . $newNumber;
    }

    /**
     * Get available payment methods
     */
    public function paymentMethods()
    {
        try {
            $methods = [
                [
                    'id' => 'cash',
                    'name' => 'Cash on Delivery',
                    'description' => 'Pay when your order is delivered',
                    'icon' => 'fas fa-money-bill-wave',
                    'enabled' => true
                ],
                [
                    'id' => 'card',
                    'name' => 'Credit/Debit Card',
                    'description' => 'Pay securely with your card',
                    'icon' => 'fas fa-credit-card',
                    'enabled' => true
                ],
                [
                    'id' => 'wallet',
                    'name' => 'Wallet',
                    'description' => 'Pay from your wallet balance',
                    'icon' => 'fas fa-wallet',
                    'enabled' => true
                ]
            ];

            $response = [
                'status' => true,
                'data' => ['methods' => $methods],
                'message' => 'Payment methods fetched successfully'
            ];

            return comman_custom_response($response);

        } catch (\Exception $e) {
            return comman_message_response(__('messages.failed'));
        }
    }
}
