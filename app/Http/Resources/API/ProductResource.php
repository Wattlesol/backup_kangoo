<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'base_price' => $this->base_price,
            'effective_price' => $this->effective_price,
            'final_price' => $this->final_price ?? $this->effective_price,
            'price_format' => getPriceFormat($this->final_price ?? $this->effective_price),
            'admin_price_active' => $this->admin_price_active,
            'admin_override_price' => $this->admin_override_price,
            'price_override_type' => $this->price_override_type,
            'weight' => $this->weight,
            'dimensions' => $this->dimensions,
            'track_inventory' => $this->track_inventory,
            'stock_quantity' => $this->stock_quantity,
            'low_stock_threshold' => $this->low_stock_threshold,
            'is_featured' => $this->is_featured,
            'status' => $this->status,
            'is_in_stock' => $this->is_in_stock,
            'is_low_stock' => $this->is_low_stock,
            'sort_order' => $this->sort_order,
            'created_by_type' => $this->created_by_type,
            
            // Store-specific data (if available)
            'store_price' => $this->store_price ?? null,
            'store_stock' => $this->store_stock ?? null,
            
            // Relationships
            'category' => $this->whenLoaded('category', function() {
                return new ProductCategoryResource($this->category);
            }),
            
            'creator' => $this->whenLoaded('creator', function() {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->display_name,
                    'type' => $this->creator->user_type
                ];
            }),
            
            'variants' => $this->whenLoaded('variants', function() {
                return ProductVariantResource::collection($this->variants->where('status', true));
            }),
            
            // Media
            'main_image' => $this->main_image,
            'gallery' => $this->gallery,
            
            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
