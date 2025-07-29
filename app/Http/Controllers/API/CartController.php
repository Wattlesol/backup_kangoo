<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ShoppingCart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Http\Resources\API\CartResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $cartSummary = ShoppingCart::getCartSummary($user->id);

            $response = [
                'status' => true,
                'data' => [
                    'items' => CartResource::collection($cartSummary['items']),
                    'total_items' => $cartSummary['total_items'],
                    'subtotal' => $cartSummary['subtotal'],
                    'stores_count' => $cartSummary['stores']->count(),
                    'admin_products_count' => $cartSummary['admin_products']->count()
                ],
                'message' => __('messages.list_fetch_successfully', ['item' => __('messages.cart')])
            ];

            return comman_custom_response($response);

        } catch (\Exception $e) {
            return comman_message_response(__('messages.failed'));
        }
    }

    public function add(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|exists:products,id',
                'product_variant_id' => 'nullable|exists:product_variants,id',
                'store_id' => 'nullable|exists:stores,id',
                'quantity' => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                return comman_message_response($validator->errors()->first());
            }

            $user = Auth::user();
            $productId = $request->product_id;
            $variantId = $request->product_variant_id;
            $storeId = $request->store_id;
            $quantity = $request->quantity;

            // Validate product exists and is active
            $product = Product::active()->findOrFail($productId);

            // Validate variant if provided
            if ($variantId) {
                $variant = ProductVariant::where('product_id', $productId)
                                       ->active()
                                       ->findOrFail($variantId);
                
                // Check variant stock
                if (!$variant->is_in_stock) {
                    return comman_message_response(__('messages.product_out_of_stock'));
                }
            } else {
                // Check product stock
                if (!$product->is_in_stock) {
                    return comman_message_response(__('messages.product_out_of_stock'));
                }
            }

            // Validate store if provided
            if ($storeId) {
                $store = Store::approved()->active()->findOrFail($storeId);
                
                // Check if product is available in this store
                $storeProduct = $store->storeProducts()
                                    ->where('product_id', $productId)
                                    ->where('is_available', true)
                                    ->first();
                
                if (!$storeProduct) {
                    return comman_message_response(__('messages.product_not_available_in_store'));
                }

                // Check if quantity is within limits
                if (!$storeProduct->canOrder($quantity)) {
                    return comman_message_response(__('messages.invalid_quantity'));
                }
            }

            // Add to cart
            $cartItem = ShoppingCart::addToCart($user->id, $productId, $variantId, $storeId, $quantity);

            $response = [
                'status' => true,
                'data' => new CartResource($cartItem),
                'message' => __('messages.item_added_to_cart')
            ];

            return comman_custom_response($response);

        } catch (\Exception $e) {
            return comman_message_response(__('messages.failed'));
        }
    }

    public function update(Request $request, $cartItemId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'quantity' => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                return comman_message_response($validator->errors()->first());
            }

            $user = Auth::user();
            $cartItem = ShoppingCart::byUser($user->id)->findOrFail($cartItemId);
            $quantity = $request->quantity;

            // Validate stock availability
            if ($cartItem->product_variant_id) {
                $variant = $cartItem->productVariant;
                if ($variant->track_inventory && $quantity > $variant->stock_quantity) {
                    return comman_message_response(__('messages.insufficient_stock'));
                }
            } else {
                $product = $cartItem->product;
                if ($product->track_inventory && $quantity > $product->stock_quantity) {
                    return comman_message_response(__('messages.insufficient_stock'));
                }
            }

            // Validate store product limits if applicable
            if ($cartItem->store_id) {
                $storeProduct = $cartItem->store->storeProducts()
                                               ->where('product_id', $cartItem->product_id)
                                               ->first();
                
                if ($storeProduct && !$storeProduct->canOrder($quantity)) {
                    return comman_message_response(__('messages.invalid_quantity'));
                }
            }

            $cartItem->updateQuantity($quantity);

            $response = [
                'status' => true,
                'data' => new CartResource($cartItem->fresh()),
                'message' => __('messages.cart_updated_successfully')
            ];

            return comman_custom_response($response);

        } catch (\Exception $e) {
            return comman_message_response(__('messages.failed'));
        }
    }

    public function remove($cartItemId)
    {
        try {
            $user = Auth::user();
            $cartItem = ShoppingCart::byUser($user->id)->findOrFail($cartItemId);
            
            $cartItem->delete();

            $response = [
                'status' => true,
                'message' => __('messages.item_removed_from_cart')
            ];

            return comman_custom_response($response);

        } catch (\Exception $e) {
            return comman_message_response(__('messages.failed'));
        }
    }

    public function clear()
    {
        try {
            $user = Auth::user();
            ShoppingCart::clearCart($user->id);

            $response = [
                'status' => true,
                'message' => __('messages.cart_cleared_successfully')
            ];

            return comman_custom_response($response);

        } catch (\Exception $e) {
            return comman_message_response(__('messages.failed'));
        }
    }

    public function count()
    {
        try {
            $user = Auth::user();
            $count = ShoppingCart::getCartCount($user->id);

            $response = [
                'status' => true,
                'data' => ['count' => $count],
                'message' => __('messages.cart_count_fetched')
            ];

            return comman_custom_response($response);

        } catch (\Exception $e) {
            return comman_message_response(__('messages.failed'));
        }
    }

    /**
     * Validate cart before checkout
     */
    public function validate()
    {
        try {
            $user = Auth::user();
            $errors = ShoppingCart::validateCart($user->id);

            if (!empty($errors)) {
                return comman_custom_response([
                    'status' => false,
                    'data' => ['errors' => $errors],
                    'message' => 'Cart validation failed'
                ]);
            }

            $response = [
                'status' => true,
                'message' => 'Cart is valid'
            ];

            return comman_custom_response($response);

        } catch (\Exception $e) {
            return comman_message_response(__('messages.failed'));
        }
    }

    /**
     * Get cart totals with tax and delivery
     */
    public function totals(Request $request)
    {
        try {
            $user = Auth::user();
            $deliveryAddress = $request->get('delivery_address');

            $totals = ShoppingCart::calculateTotals($user->id, $deliveryAddress);

            $response = [
                'status' => true,
                'data' => $totals,
                'message' => 'Cart totals calculated successfully'
            ];

            return comman_custom_response($response);

        } catch (\Exception $e) {
            return comman_message_response(__('messages.failed'));
        }
    }

    /**
     * Check if product is in cart
     */
    public function checkProduct(Request $request)
    {
        try {
            $user = Auth::user();
            $productId = $request->get('product_id');
            $variantId = $request->get('variant_id');
            $storeId = $request->get('store_id');

            $inCart = ShoppingCart::isInCart($user->id, $productId, $variantId, $storeId);

            $response = [
                'status' => true,
                'data' => ['in_cart' => $inCart],
                'message' => 'Product check completed'
            ];

            return comman_custom_response($response);

        } catch (\Exception $e) {
            return comman_message_response(__('messages.failed'));
        }
    }
}
