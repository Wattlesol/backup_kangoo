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
}
