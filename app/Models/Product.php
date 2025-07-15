<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Product extends BaseModel implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $table = 'products';

    protected $fillable = [
        'name',
        'description',
        'short_description',
        'slug',
        'sku',
        'product_category_id',
        'created_by',
        'created_by_type',
        'base_price',
        'admin_override_price',
        'admin_price_active',
        'price_override_type',
        'weight',
        'dimensions',
        'track_inventory',
        'stock_quantity',
        'low_stock_threshold',
        'is_featured',
        'status',
        'meta_data',
        'sort_order'
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'admin_override_price' => 'decimal:2',
        'admin_price_active' => 'boolean',
        'weight' => 'decimal:2',
        'dimensions' => 'array',
        'track_inventory' => 'boolean',
        'stock_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
        'is_featured' => 'boolean',
        'status' => 'boolean',
        'meta_data' => 'array',
        'sort_order' => 'integer'
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function storeProducts()
    {
        return $this->hasMany(StoreProduct::class);
    }

    public function stores()
    {
        return $this->belongsToMany(Store::class, 'store_products')
                    ->withPivot(['store_price', 'stock_quantity', 'is_available'])
                    ->withTimestamps();
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

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('product_category_id', $categoryId);
    }

    public function scopeCreatedBy($query, $userId, $type = null)
    {
        $query = $query->where('created_by', $userId);
        if ($type) {
            $query->where('created_by_type', $type);
        }
        return $query;
    }

    // Accessors
    public function getEffectivePriceAttribute()
    {
        if ($this->admin_price_active && $this->admin_override_price) {
            return $this->admin_override_price;
        }
        return $this->base_price;
    }

    public function getMainImageAttribute()
    {
        return getSingleMedia($this, 'product_images', null);
    }

    public function getGalleryAttribute()
    {
        return $this->getMedia('product_images');
    }

    public function getIsInStockAttribute()
    {
        if (!$this->track_inventory) {
            return true;
        }
        return $this->stock_quantity > 0;
    }

    public function getIsLowStockAttribute()
    {
        if (!$this->track_inventory) {
            return false;
        }
        return $this->stock_quantity <= $this->low_stock_threshold;
    }

    // Methods
    public function getLowestStorePrice($storeId = null)
    {
        $query = $this->storeProducts()->where('is_available', true);

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        return $query->min('store_price') ?? $this->effective_price;
    }

    public function getHighestStorePrice($storeId = null)
    {
        $query = $this->storeProducts()->where('is_available', true);

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        return $query->max('store_price') ?? $this->effective_price;
    }

    public function getFinalPrice($storeId = null)
    {
        if ($this->admin_price_active && $this->admin_override_price) {
            switch ($this->price_override_type) {
                case 'lowest':
                    return min($this->admin_override_price, $this->getLowestStorePrice($storeId));
                case 'highest':
                    return max($this->admin_override_price, $this->getHighestStorePrice($storeId));
                case 'fixed':
                default:
                    return $this->admin_override_price;
            }
        }

        return $this->getLowestStorePrice($storeId);
    }

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
}
