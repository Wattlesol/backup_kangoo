<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ProductVariant extends BaseModel implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $table = 'product_variants';

    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'attributes',
        'price_adjustment',
        'weight',
        'dimensions',
        'stock_quantity',
        'track_inventory',
        'status',
        'sort_order'
    ];

    protected $casts = [
        'attributes' => 'array',
        'price_adjustment' => 'decimal:2',
        'weight' => 'decimal:2',
        'dimensions' => 'array',
        'stock_quantity' => 'integer',
        'track_inventory' => 'boolean',
        'status' => 'boolean',
        'sort_order' => 'integer'
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function cartItems()
    {
        return $this->hasMany(ShoppingCart::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    public function scopeByAttribute($query, $attribute, $value)
    {
        return $query->whereJsonContains('attributes->' . $attribute, $value);
    }

    // Accessors
    public function getFinalPriceAttribute()
    {
        return $this->product->effective_price + $this->price_adjustment;
    }

    public function getVariantImageAttribute()
    {
        return getSingleMedia($this, 'variant_images', null) ?? $this->product->main_image;
    }

    public function getIsInStockAttribute()
    {
        if (!$this->track_inventory) {
            return $this->product->is_in_stock;
        }
        return $this->stock_quantity > 0;
    }

    public function getAttributeDisplayAttribute()
    {
        if (!$this->attributes) {
            return '';
        }

        $display = [];
        foreach ($this->attributes as $key => $value) {
            $display[] = ucfirst($key) . ': ' . ucfirst($value);
        }

        return implode(', ', $display);
    }

    // Methods
    public function decreaseStock($quantity)
    {
        if ($this->track_inventory) {
            $this->decrement('stock_quantity', $quantity);
        } else {
            $this->product->decreaseStock($quantity);
        }
    }

    public function increaseStock($quantity)
    {
        if ($this->track_inventory) {
            $this->increment('stock_quantity', $quantity);
        } else {
            $this->product->increaseStock($quantity);
        }
    }
}
