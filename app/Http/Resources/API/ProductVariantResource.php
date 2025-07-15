<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
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
            'sku' => $this->sku,
            'attributes' => $this->attributes,
            'attribute_display' => $this->attribute_display,
            'price_adjustment' => $this->price_adjustment,
            'final_price' => $this->final_price,
            'price_format' => getPriceFormat($this->final_price),
            'weight' => $this->weight,
            'dimensions' => $this->dimensions,
            'stock_quantity' => $this->stock_quantity,
            'track_inventory' => $this->track_inventory,
            'status' => $this->status,
            'is_in_stock' => $this->is_in_stock,
            'sort_order' => $this->sort_order,
            'variant_image' => $this->variant_image,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
