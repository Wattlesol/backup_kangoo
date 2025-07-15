<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'order_number' => $this->order_number,
            'formatted_order_number' => $this->formatted_order_number,
            'order_type' => $this->order_type,
            'is_admin_order' => $this->is_admin_order,
            'status' => $this->status,
            'status_color' => $this->status_color,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'payment_transaction_id' => $this->payment_transaction_id,
            
            // Amounts
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'delivery_fee' => $this->delivery_fee,
            'discount_amount' => $this->discount_amount,
            'total_amount' => $this->total_amount,
            'currency' => $this->currency,
            
            // Formatted amounts
            'subtotal_format' => getPriceFormat($this->subtotal),
            'tax_amount_format' => getPriceFormat($this->tax_amount),
            'delivery_fee_format' => getPriceFormat($this->delivery_fee),
            'discount_amount_format' => getPriceFormat($this->discount_amount),
            'total_amount_format' => getPriceFormat($this->total_amount),
            
            // Delivery information
            'delivery_address' => $this->delivery_address,
            'delivery_phone' => $this->delivery_phone,
            'delivery_notes' => $this->delivery_notes,
            'estimated_delivery_at' => $this->estimated_delivery_at,
            'delivered_at' => $this->delivered_at,
            
            // Order management
            'can_be_cancelled' => $this->can_be_cancelled,
            'notes' => $this->notes,
            'cancellation_reason' => $this->cancellation_reason,
            'cancelled_at' => $this->cancelled_at,
            
            // Customer details
            'customer' => $this->whenLoaded('customer', function() {
                return [
                    'id' => $this->customer->id,
                    'name' => $this->customer->display_name,
                    'email' => $this->customer->email,
                    'phone' => $this->customer->contact_number
                ];
            }),
            
            // Store details (if applicable)
            'store' => $this->when($this->store_id, function() {
                return $this->whenLoaded('store', function() {
                    return [
                        'id' => $this->store->id,
                        'name' => $this->store->name,
                        'phone' => $this->store->phone,
                        'address' => $this->store->address,
                        'delivery_fee' => $this->store->delivery_fee,
                        'minimum_order_amount' => $this->store->minimum_order_amount,
                        'logo' => $this->store->logo,
                        'provider' => [
                            'id' => $this->store->provider->id,
                            'name' => $this->store->provider->display_name,
                            'phone' => $this->store->provider->contact_number
                        ]
                    ];
                });
            }),
            
            // Order items
            'items' => $this->whenLoaded('items', function() {
                return $this->items->map(function($item) {
                    return [
                        'id' => $item->id,
                        'product_name' => $item->product_name,
                        'product_sku' => $item->product_sku,
                        'display_name' => $item->display_name,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'total_price' => $item->total_price,
                        'unit_price_format' => getPriceFormat($item->unit_price),
                        'total_price_format' => getPriceFormat($item->total_price),
                        'product_image' => $item->product_image,
                        'variant_details' => $item->variant_details,
                        'variant_display_name' => $item->variant_display_name
                    ];
                });
            }),
            
            // Status history
            'status_histories' => $this->whenLoaded('statusHistories', function() {
                return $this->statusHistories->map(function($history) {
                    return [
                        'status' => $history->status,
                        'status_label' => $history->status_label,
                        'notes' => $history->notes,
                        'changed_by' => $history->changed_by_name,
                        'changed_at' => $history->changed_at
                    ];
                });
            }),
            
            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
