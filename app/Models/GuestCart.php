<?php

namespace App\Models;

use Illuminate\Support\Facades\Session;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;

class GuestCart
{
    const SESSION_KEY = 'guest_cart';

    /**
     * Add item to guest cart
     */
    public static function addItem($productId, $variantId = null, $storeId = null, $quantity = 1)
    {
        $cart = self::getCart();
        
        $product = Product::findOrFail($productId);
        $variant = $variantId ? ProductVariant::findOrFail($variantId) : null;
        
        // Determine the price
        $price = $variant ? $variant->final_price : $product->getFinalPrice($storeId);
        
        // Create item key
        $itemKey = self::generateItemKey($productId, $variantId, $storeId);
        
        if (isset($cart[$itemKey])) {
            // Update existing item
            $cart[$itemKey]['quantity'] += $quantity;
        } else {
            // Add new item
            $cart[$itemKey] = [
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'store_id' => $storeId,
                'quantity' => $quantity,
                'unit_price' => $price,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'product_image' => $product->getFirstMediaUrl('product_images'),
                'variant_name' => $variant ? $variant->attribute_display : null,
                'store_name' => $storeId ? Store::find($storeId)->name : null,
                'added_at' => now()->toDateTimeString()
            ];
        }
        
        self::saveCart($cart);
        
        return $cart[$itemKey];
    }

    /**
     * Update item quantity
     */
    public static function updateItem($itemKey, $quantity)
    {
        $cart = self::getCart();
        
        if (isset($cart[$itemKey])) {
            if ($quantity <= 0) {
                unset($cart[$itemKey]);
            } else {
                $cart[$itemKey]['quantity'] = $quantity;
            }
            
            self::saveCart($cart);
            return true;
        }
        
        return false;
    }

    /**
     * Remove item from cart
     */
    public static function removeItem($itemKey)
    {
        $cart = self::getCart();
        
        if (isset($cart[$itemKey])) {
            unset($cart[$itemKey]);
            self::saveCart($cart);
            return true;
        }
        
        return false;
    }

    /**
     * Clear entire cart
     */
    public static function clearCart()
    {
        Session::forget(self::SESSION_KEY);
    }

    /**
     * Get cart contents
     */
    public static function getCart()
    {
        return Session::get(self::SESSION_KEY, []);
    }

    /**
     * Get cart summary
     */
    public static function getCartSummary()
    {
        $cart = self::getCart();
        $items = [];
        $totalItems = 0;
        $subtotal = 0;

        foreach ($cart as $itemKey => $item) {
            $totalPrice = $item['quantity'] * $item['unit_price'];
            $item['total_price'] = $totalPrice;
            $item['item_key'] = $itemKey;
            
            $items[] = $item;
            $totalItems += $item['quantity'];
            $subtotal += $totalPrice;
        }

        return [
            'items' => $items,
            'total_items' => $totalItems,
            'subtotal' => $subtotal,
            'stores' => collect($items)->whereNotNull('store_id')->groupBy('store_id'),
            'admin_products' => collect($items)->whereNull('store_id')
        ];
    }

    /**
     * Get cart count
     */
    public static function getCartCount()
    {
        $cart = self::getCart();
        return array_sum(array_column($cart, 'quantity'));
    }

    /**
     * Check if product is in cart
     */
    public static function isInCart($productId, $variantId = null, $storeId = null)
    {
        $cart = self::getCart();
        $itemKey = self::generateItemKey($productId, $variantId, $storeId);
        
        return isset($cart[$itemKey]);
    }

    /**
     * Validate cart items
     */
    public static function validateCart()
    {
        $cart = self::getCart();
        $errors = [];
        $validCart = [];

        foreach ($cart as $itemKey => $item) {
            $product = Product::find($item['product_id']);
            
            // Check if product still exists and is active
            if (!$product || !$product->is_active) {
                $errors[] = "Product '{$item['product_name']}' is no longer available";
                continue;
            }

            // Check variant if applicable
            if ($item['product_variant_id']) {
                $variant = ProductVariant::find($item['product_variant_id']);
                if (!$variant || !$variant->is_active) {
                    $errors[] = "Product variant for '{$item['product_name']}' is no longer available";
                    continue;
                }
            }

            // Check stock availability
            if ($item['store_id']) {
                $store = Store::find($item['store_id']);
                if (!$store || !$store->is_active) {
                    $errors[] = "Store '{$item['store_name']}' is no longer available";
                    continue;
                }

                $storeProduct = $store->storeProducts()
                                    ->where('product_id', $item['product_id'])
                                    ->where('is_available', true)
                                    ->first();
                
                if (!$storeProduct || !$storeProduct->canOrder($item['quantity'])) {
                    $errors[] = "Insufficient stock for '{$item['product_name']}' in {$item['store_name']}";
                    continue;
                }
            } else {
                // Check admin product stock
                if ($product->stock_quantity < $item['quantity']) {
                    $errors[] = "Insufficient stock for '{$item['product_name']}'";
                    continue;
                }
            }

            // Item is valid, keep it
            $validCart[$itemKey] = $item;
        }

        // Update cart with only valid items
        if (count($validCart) !== count($cart)) {
            self::saveCart($validCart);
        }

        return $errors;
    }

    /**
     * Calculate cart totals
     */
    public static function calculateTotals($deliveryAddress = null)
    {
        $summary = self::getCartSummary();
        $subtotal = $summary['subtotal'];
        
        // Calculate tax (10% tax rate)
        $taxRate = 0.10;
        $taxAmount = $subtotal * $taxRate;
        
        // Calculate delivery fee
        $deliveryFee = 0;
        if ($deliveryAddress) {
            $deliveryFee = 5.00; // Flat delivery fee
        }
        
        // Apply discounts (can be extended for coupons)
        $discountAmount = 0;
        
        $total = $subtotal + $taxAmount + $deliveryFee - $discountAmount;
        
        return [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'delivery_fee' => $deliveryFee,
            'discount_amount' => $discountAmount,
            'total_amount' => $total,
            'items' => $summary['items'],
            'total_items' => $summary['total_items']
        ];
    }

    /**
     * Transfer guest cart to user cart
     */
    public static function transferToUser($userId)
    {
        $guestCart = self::getCart();
        
        foreach ($guestCart as $item) {
            ShoppingCart::addToCart(
                $userId,
                $item['product_id'],
                $item['product_variant_id'],
                $item['store_id'],
                $item['quantity']
            );
        }
        
        self::clearCart();
    }

    /**
     * Generate unique item key
     */
    private static function generateItemKey($productId, $variantId = null, $storeId = null)
    {
        return md5($productId . '_' . $variantId . '_' . $storeId);
    }

    /**
     * Save cart to session
     */
    private static function saveCart($cart)
    {
        Session::put(self::SESSION_KEY, $cart);
    }
}
