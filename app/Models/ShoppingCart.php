<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShoppingCart extends BaseModel
{
    use HasFactory;

    protected $table = 'shopping_carts';

    protected $fillable = [
        'user_id',
        'product_id',
        'product_variant_id',
        'store_id',
        'quantity',
        'unit_price'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    // Scopes
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeAdminProducts($query)
    {
        return $query->whereNull('store_id');
    }

    public function scopeStoreProducts($query)
    {
        return $query->whereNotNull('store_id');
    }

    // Accessors
    public function getTotalPriceAttribute()
    {
        return $this->quantity * $this->unit_price;
    }

    public function getDisplayNameAttribute()
    {
        if ($this->product_variant_id && $this->productVariant) {
            return $this->product->name . ' (' . $this->productVariant->attribute_display . ')';
        }
        return $this->product->name;
    }

    public function getProductImageAttribute()
    {
        if ($this->product_variant_id && $this->productVariant) {
            return $this->productVariant->variant_image;
        }
        return $this->product->main_image;
    }

    public function getIsAdminProductAttribute()
    {
        return is_null($this->store_id);
    }

    // Methods
    public function updateQuantity($quantity)
    {
        if ($quantity <= 0) {
            $this->delete();
            return false;
        }

        $this->update(['quantity' => $quantity]);
        return true;
    }

    public function increaseQuantity($amount = 1)
    {
        $this->increment('quantity', $amount);
    }

    public function decreaseQuantity($amount = 1)
    {
        $newQuantity = $this->quantity - $amount;
        return $this->updateQuantity($newQuantity);
    }

    public static function addToCart($userId, $productId, $variantId = null, $storeId = null, $quantity = 1)
    {
        $product = Product::findOrFail($productId);
        $variant = $variantId ? ProductVariant::findOrFail($variantId) : null;

        // Determine the price
        $price = $variant ? $variant->final_price : $product->getFinalPrice($storeId);

        // Check if item already exists in cart
        $existingItem = self::where('user_id', $userId)
                           ->where('product_id', $productId)
                           ->where('product_variant_id', $variantId)
                           ->where('store_id', $storeId)
                           ->first();

        if ($existingItem) {
            $existingItem->increaseQuantity($quantity);
            return $existingItem;
        }

        return self::create([
            'user_id' => $userId,
            'product_id' => $productId,
            'product_variant_id' => $variantId,
            'store_id' => $storeId,
            'quantity' => $quantity,
            'unit_price' => $price
        ]);
    }

    public static function getCartSummary($userId)
    {
        $cartItems = self::byUser($userId)->with(['product', 'productVariant', 'store'])->get();
        
        $summary = [
            'items' => $cartItems,
            'total_items' => $cartItems->sum('quantity'),
            'subtotal' => $cartItems->sum('total_price'),
            'stores' => $cartItems->whereNotNull('store_id')->groupBy('store_id'),
            'admin_products' => $cartItems->whereNull('store_id')
        ];

        return $summary;
    }

    public static function clearCart($userId)
    {
        return self::byUser($userId)->delete();
    }

    /**
     * Get cart count for user
     */
    public static function getCartCount($userId)
    {
        return self::byUser($userId)->sum('quantity');
    }

    /**
     * Check if product is already in cart
     */
    public static function isInCart($userId, $productId, $variantId = null, $storeId = null)
    {
        return self::byUser($userId)
                   ->where('product_id', $productId)
                   ->where('product_variant_id', $variantId)
                   ->where('store_id', $storeId)
                   ->exists();
    }

    /**
     * Get cart items grouped by store
     */
    public static function getCartByStores($userId)
    {
        $cartItems = self::byUser($userId)->with(['product', 'productVariant', 'store'])->get();

        $grouped = [
            'admin_products' => $cartItems->whereNull('store_id'),
            'store_products' => $cartItems->whereNotNull('store_id')->groupBy('store_id')
        ];

        return $grouped;
    }

    /**
     * Validate cart items before checkout
     */
    public static function validateCart($userId)
    {
        $cartItems = self::byUser($userId)->with(['product', 'productVariant', 'store'])->get();
        $errors = [];

        foreach ($cartItems as $item) {
            // Check if product is still active
            if (!$item->product || !$item->product->is_active) {
                $errors[] = "Product '{$item->product->name}' is no longer available";
                continue;
            }

            // Check if variant is still active (if applicable)
            if ($item->product_variant_id && (!$item->productVariant || !$item->productVariant->is_active)) {
                $errors[] = "Product variant for '{$item->product->name}' is no longer available";
                continue;
            }

            // Check stock availability
            if ($item->store_id) {
                $storeProduct = $item->store->storeProducts()
                                          ->where('product_id', $item->product_id)
                                          ->where('is_available', true)
                                          ->first();

                if (!$storeProduct || !$storeProduct->canOrder($item->quantity)) {
                    $errors[] = "Insufficient stock for '{$item->product->name}' in {$item->store->name}";
                }
            } else {
                // Check admin product stock
                if ($item->product->stock_quantity < $item->quantity) {
                    $errors[] = "Insufficient stock for '{$item->product->name}'";
                }
            }

            // Check if store is still active (if applicable)
            if ($item->store_id && (!$item->store || !$item->store->is_active)) {
                $errors[] = "Store '{$item->store->name}' is no longer available";
            }
        }

        return $errors;
    }

    /**
     * Calculate cart totals with tax and delivery
     */
    public static function calculateTotals($userId, $deliveryAddress = null)
    {
        $cartSummary = self::getCartSummary($userId);
        $subtotal = $cartSummary['subtotal'];

        // Calculate tax (you can customize this based on your tax rules)
        $taxRate = 0.10; // 10% tax
        $taxAmount = $subtotal * $taxRate;

        // Calculate delivery fee (you can customize this based on distance, weight, etc.)
        $deliveryFee = 0;
        if ($deliveryAddress) {
            $deliveryFee = 5.00; // Flat delivery fee
        }

        // Apply any discounts (you can add coupon logic here)
        $discountAmount = 0;

        $total = $subtotal + $taxAmount + $deliveryFee - $discountAmount;

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'delivery_fee' => $deliveryFee,
            'discount_amount' => $discountAmount,
            'total_amount' => $total,
            'items' => $cartSummary['items'],
            'total_items' => $cartSummary['total_items']
        ];
    }
}
