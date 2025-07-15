<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StoreProduct extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'store_products';

    protected $fillable = [
        'store_id',
        'product_id',
        'store_price',
        'stock_quantity',
        'track_inventory',
        'is_available',
        'minimum_order_quantity',
        'maximum_order_quantity',
        'store_notes'
    ];

    protected $casts = [
        'store_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'track_inventory' => 'boolean',
        'is_available' => 'boolean',
        'minimum_order_quantity' => 'integer',
        'maximum_order_quantity' => 'integer'
    ];

    // Relationships
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    // Accessors
    public function getIsInStockAttribute()
    {
        if (!$this->track_inventory) {
            return $this->product->is_in_stock;
        }
        return $this->stock_quantity > 0;
    }

    public function getFinalPriceAttribute()
    {
        $productPrice = $this->product->getFinalPrice($this->store_id);
        
        // If admin has dynamic pricing enabled, compare with store price
        if ($this->product->admin_price_active && $this->product->admin_override_price) {
            switch ($this->product->price_override_type) {
                case 'lowest':
                    return min($productPrice, $this->store_price);
                case 'highest':
                    return max($productPrice, $this->store_price);
                case 'fixed':
                    return $productPrice;
                default:
                    return $this->store_price;
            }
        }
        
        return $this->store_price;
    }

    // Methods
    public function decreaseStock($quantity)
    {
        if ($this->track_inventory) {
            $this->decrement('stock_quantity', $quantity);
        }
    }

    public function increaseStock($quantity)
    {
        if ($this->track_inventory) {
            $this->increment('stock_quantity', $quantity);
        }
    }

    public function canOrder($quantity)
    {
        // Check minimum quantity
        if ($quantity < $this->minimum_order_quantity) {
            return false;
        }

        // Check maximum quantity
        if ($this->maximum_order_quantity && $quantity > $this->maximum_order_quantity) {
            return false;
        }

        // Check stock availability
        if ($this->track_inventory && $quantity > $this->stock_quantity) {
            return false;
        }

        return $this->is_available;
    }
}
