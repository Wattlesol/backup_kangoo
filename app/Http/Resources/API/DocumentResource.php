<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
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
            'id'            => $this->id,
            'name'          => $this->name,
            'name_ar'       => $this->name_ar,
            'localized_name'=> $this->localized_name,
            'status'        => $this->status,
            'is_required'   => $this->is_required,
            'deleted_at'        => $this->deleted_at,
        ];
    }
}
