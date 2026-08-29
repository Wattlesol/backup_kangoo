<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Resources\Json\JsonResource;

class ServiceAddonResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'name_ar'       => $this->name_ar,
            'service_id'    => $this->service_id,
            'service_name'  => optional($this->service)->name ?: 'All Services',
            'category_ids'  => $this->categories->pluck('id')->values(),
            'category_names'=> $this->categories->pluck('name')->values(),
            'service_ids'   => $this->services->pluck('id')->values(),
            'service_names' => $this->services->pluck('name')->values(),
            'price'         => $this->price,
            'status'        => $this->status,
            'serviceaddon_image' => optional($this->getMedia('serviceaddon_image')->first())->getUrl(),
        ];
    }
}
