<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $extention = imageExtention(getSingleMedia($this, 'category_image',null));
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'name_ar'       => $this->name_ar,
            'name_en'       => $this->name_en ?: $this->name,
            'status'        => $this->status,
            'description'   => $this->description,
            'is_featured'   => $this->is_featured,
            'color'         => $this->color,
            'display_order' => $this->display_order,
            'icon'          => $this->icon,
            'category_icon' => getSingleMedia($this, 'category_icon', null),
            'category_image'=> getSingleMedia($this, 'category_image',null),
            'category_extension' => $extention,
            'services' => $this->services->count(),
            'deleted_at'        => $this->deleted_at,
        ];
    }
}
