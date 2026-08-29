<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Resources\Json\JsonResource;

class ProviderDocumentResource extends JsonResource
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
            'id'                        => $this->id,
            'provider_id'               => $this->provider_id,
            'document_id'               => $this->document_id,
            'document_name'             => optional($this->document)->localized_name,
            'document_name_en'          => optional($this->document)->name,
            'document_name_ar'          => optional($this->document)->name_ar,
            'is_verified'               => $this->is_verified,
            'provider_document'         => getSingleMedia($this, 'provider_document',null),
            'deleted_at'        => $this->deleted_at,
        ];
    }
}
