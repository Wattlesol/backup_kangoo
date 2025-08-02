<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Resources\Json\JsonResource;

class StoreResource extends JsonResource
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
            'slug' => $this->slug,
            'phone' => $this->phone,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
            'is_active' => $this->is_active,
            'business_hours' => $this->business_hours,
            'delivery_radius' => $this->delivery_radius,
            'minimum_order_amount' => $this->minimum_order_amount,
            'delivery_fee' => $this->delivery_fee,
            'is_open' => $this->is_open,
            'distance' => $this->distance ?? null,
            
            // Location details
            'country' => $this->whenLoaded('country', function() {
                return [
                    'id' => $this->country->id,
                    'name' => $this->country->name
                ];
            }),
            
            'state' => $this->whenLoaded('state', function() {
                return [
                    'id' => $this->state->id,
                    'name' => $this->state->name
                ];
            }),
            
            'city' => $this->whenLoaded('city', function() {
                return [
                    'id' => $this->city->id,
                    'name' => $this->city->name
                ];
            }),
            
            // Created by details (admin user who created the store)
            'created_by' => $this->whenLoaded('createdBy', function() {
                return [
                    'id' => $this->createdBy->id,
                    'name' => $this->createdBy->display_name ?? $this->createdBy->first_name . ' ' . $this->createdBy->last_name,
                    'email' => $this->createdBy->email,
                ];
            }),
            
            // Media
            'logo' => $this->logo,
            
            // Timestamps
            'approved_at' => $this->approved_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
