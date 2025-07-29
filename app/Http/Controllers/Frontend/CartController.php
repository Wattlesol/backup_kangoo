<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\ShoppingCart;
use App\Models\GuestCart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;

class CartController extends Controller
{
    /**
     * Add item to cart (works for both authenticated and guest users)
     */
    public function add(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|exists:products,id',
                'variant_id' => 'nullable|exists:product_variants,id',
                'store_id' => 'nullable|exists:stores,id',
                'quantity' => 'required|integer|min:1|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $productId = $request->product_id;
            $variantId = $request->variant_id;
            $storeId = $request->store_id;
            $quantity = $request->quantity;

            // Validate product availability
            $product = Product::findOrFail($productId);
            if (!$product->is_active) {
                return response()->json([
                    'status' => false,
                    'message' => 'Product is not available'
                ], 400);
            }

            // Validate variant if provided
            if ($variantId) {
                $variant = ProductVariant::findOrFail($variantId);
                if (!$variant->is_active) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Product variant is not available'
                    ], 400);
                }
            }

            // Validate store if provided
            if ($storeId) {
                $store = Store::approved()->active()->findOrFail($storeId);
                
                $storeProduct = $store->storeProducts()
                                    ->where('product_id', $productId)
                                    ->where('is_available', true)
                                    ->first();
                
                if (!$storeProduct || !$storeProduct->canOrder($quantity)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Product is not available in sufficient quantity'
                    ], 400);
                }
            }

            // Add to appropriate cart
            if (Auth::check()) {
                $cartItem = ShoppingCart::addToCart(Auth::id(), $productId, $variantId, $storeId, $quantity);
                $cartCount = ShoppingCart::getCartCount(Auth::id());
            } else {
                $cartItem = GuestCart::addItem($productId, $variantId, $storeId, $quantity);
                $cartCount = GuestCart::getCartCount();
            }

            return response()->json([
                'status' => true,
                'message' => 'Item added to cart successfully',
                'data' => [
                    'cart_count' => $cartCount,
                    'item' => $cartItem
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to add item to cart'
            ], 500);
        }
    }

    /**
     * Update cart item quantity
     */
    public function update(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'item_key' => 'required|string',
                'quantity' => 'required|integer|min:1|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $itemKey = $request->item_key;
            $quantity = $request->quantity;

            if (Auth::check()) {
                // For authenticated users, item_key is the cart item ID
                $cartItem = ShoppingCart::byUser(Auth::id())->findOrFail($itemKey);
                
                // Validate quantity limits
                if ($cartItem->store_id) {
                    $storeProduct = $cartItem->store->storeProducts()
                                                   ->where('product_id', $cartItem->product_id)
                                                   ->first();
                    
                    if ($storeProduct && !$storeProduct->canOrder($quantity)) {
                        return response()->json([
                            'status' => false,
                            'message' => 'Insufficient stock available'
                        ], 400);
                    }
                }

                $cartItem->updateQuantity($quantity);
                $cartCount = ShoppingCart::getCartCount(Auth::id());
            } else {
                // For guest users, item_key is the generated hash
                $updated = GuestCart::updateItem($itemKey, $quantity);
                
                if (!$updated) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Cart item not found'
                    ], 404);
                }
                
                $cartCount = GuestCart::getCartCount();
            }

            return response()->json([
                'status' => true,
                'message' => 'Cart updated successfully',
                'data' => ['cart_count' => $cartCount]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update cart'
            ], 500);
        }
    }

    /**
     * Remove item from cart
     */
    public function remove(Request $request)
    {
        try {
            $itemKey = $request->item_key;

            if (Auth::check()) {
                $cartItem = ShoppingCart::byUser(Auth::id())->findOrFail($itemKey);
                $cartItem->delete();
                $cartCount = ShoppingCart::getCartCount(Auth::id());
            } else {
                $removed = GuestCart::removeItem($itemKey);
                
                if (!$removed) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Cart item not found'
                    ], 404);
                }
                
                $cartCount = GuestCart::getCartCount();
            }

            return response()->json([
                'status' => true,
                'message' => 'Item removed from cart',
                'data' => ['cart_count' => $cartCount]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to remove item from cart'
            ], 500);
        }
    }

    /**
     * Get cart contents
     */
    public function index()
    {
        try {
            if (Auth::check()) {
                $cartSummary = ShoppingCart::getCartSummary(Auth::id());
            } else {
                $cartSummary = GuestCart::getCartSummary();
            }

            return response()->json([
                'status' => true,
                'message' => 'Cart fetched successfully',
                'data' => $cartSummary
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch cart'
            ], 500);
        }
    }

    /**
     * Get cart count
     */
    public function count()
    {
        try {
            if (Auth::check()) {
                $count = ShoppingCart::getCartCount(Auth::id());
            } else {
                $count = GuestCart::getCartCount();
            }

            return response()->json([
                'status' => true,
                'message' => 'Cart count fetched successfully',
                'data' => ['count' => $count]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch cart count'
            ], 500);
        }
    }

    /**
     * Clear cart
     */
    public function clear()
    {
        try {
            if (Auth::check()) {
                ShoppingCart::clearCart(Auth::id());
            } else {
                GuestCart::clearCart();
            }

            return response()->json([
                'status' => true,
                'message' => 'Cart cleared successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to clear cart'
            ], 500);
        }
    }

    /**
     * Transfer guest cart to user cart (called after login)
     */
    public function transferGuestCart()
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            GuestCart::transferToUser(Auth::id());

            return response()->json([
                'status' => true,
                'message' => 'Guest cart transferred successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to transfer guest cart'
            ], 500);
        }
    }
}
