<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'product_sku',
        'product_details',
        'variant_details',
        'quantity',
        'unit_price',
        'total_price'
    ];

    protected $casts = [
        'product_details' => 'array',
        'variant_details' => 'array',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2'
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    // Accessors
    public function getDisplayNameAttribute()
    {
        if ($this->product_variant_id && $this->variant_details) {
            return $this->product_name . ' (' . $this->getVariantDisplayName() . ')';
        }
        return $this->product_name;
    }

    public function getVariantDisplayNameAttribute()
    {
        if (!$this->variant_details) {
            return '';
        }

        $display = [];
        foreach ($this->variant_details['attributes'] ?? [] as $key => $value) {
            $display[] = ucfirst($key) . ': ' . ucfirst($value);
        }

        return implode(', ', $display);
    }

    public function getProductImageAttribute()
    {
        if ($this->product_variant_id && $this->productVariant) {
            return $this->productVariant->variant_image;
        }

        if ($this->product) {
            return $this->product->main_image;
        }

        return null;
    }

    // Methods
    public function calculateTotal()
    {
        $this->total_price = $this->quantity * $this->unit_price;
        $this->save();
        return $this->total_price;
    }

    public static function createFromCartItem(ShoppingCart $cartItem, Order $order)
    {
        $product = $cartItem->product;
        $variant = $cartItem->productVariant;

        $productDetails = [
            'name' => $product->name,
            'description' => $product->description,
            'category' => $product->category->name ?? null,
            'image' => $product->main_image
        ];

        $variantDetails = null;
        if ($variant) {
            $variantDetails = [
                'name' => $variant->name,
                'attributes' => $variant->attributes,
                'image' => $variant->variant_image
            ];
        }

        return self::create([
            'order_id' => $order->id,
            'product_id' => $cartItem->product_id,
            'product_variant_id' => $cartItem->product_variant_id,
            'product_name' => $product->name,
            'product_sku' => $variant ? $variant->sku : $product->sku,
            'product_details' => $productDetails,
            'variant_details' => $variantDetails,
            'quantity' => $cartItem->quantity,
            'unit_price' => $cartItem->unit_price,
            'total_price' => $cartItem->quantity * $cartItem->unit_price
        ]);
    }
}
