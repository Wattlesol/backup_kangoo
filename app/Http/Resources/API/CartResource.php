<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'total_price' => $this->total_price,
            'price_format' => getPriceFormat($this->total_price),
            'display_name' => $this->display_name,
            'is_admin_product' => $this->is_admin_product,
            
            // Product details
            'product' => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
                'sku' => $this->product->sku,
                'main_image' => $this->product->main_image,
                'is_in_stock' => $this->product->is_in_stock,
                'category' => $this->product->category ? [
                    'id' => $this->product->category->id,
                    'name' => $this->product->category->name
                ] : null
            ],
            
            // Variant details (if applicable)
            'variant' => $this->when($this->product_variant_id, function() {
                return [
                    'id' => $this->productVariant->id,
                    'name' => $this->productVariant->name,
                    'sku' => $this->productVariant->sku,
                    'attributes' => $this->productVariant->attributes,
                    'attribute_display' => $this->productVariant->attribute_display,
                    'variant_image' => $this->productVariant->variant_image,
                    'is_in_stock' => $this->productVariant->is_in_stock
                ];
            }),
            
            // Store details (if applicable)
            'store' => $this->when($this->store_id, function() {
                return [
                    'id' => $this->store->id,
                    'name' => $this->store->name,
                    'slug' => $this->store->slug,
                    'phone' => $this->store->phone,
                    'address' => $this->store->address,
                    'delivery_fee' => $this->store->delivery_fee,
                    'minimum_order_amount' => $this->store->minimum_order_amount,
                    'logo' => $this->store->logo
                ];
            }),
            
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
