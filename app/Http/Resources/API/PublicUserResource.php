<?php

namespace App\Http\Resources\API;

use Illuminate\Http\Resources\Json\JsonResource;

class PublicUserResource extends JsonResource
{
    public function toArray($request)
    {
        $profileImage = $this->login_type !== null
            ? $this->social_image
            : getSingleMedia($this->resource, 'profile_image', null);

        $serviceRatings = $this->user_type === 'provider' && isset($this->getServiceRating)
            ? $this->getServiceRating
            : collect();
        $employeeRatings = in_array($this->user_type, ['provider', 'handyman'], true) && isset($this->handymanRating)
            ? $this->handymanRating
            : collect();

        return [
            'id' => $this->id,
            'display_name' => $this->display_name,
            'user_type' => $this->user_type,
            'description' => $this->description,
            'city_id' => $this->city_id,
            'city_name' => optional($this->city)->name,
            'profile_image' => $profileImage,
            'providertype' => optional($this->providertype)->name,
            'is_featured' => (int) $this->is_featured,
            'providers_service_rating' => $serviceRatings->isNotEmpty()
                ? (float) number_format(max($serviceRatings->avg('rating'), 0), 2)
                : 0,
            'total_service_rating' => $serviceRatings->count(),
            'employee_rating' => $employeeRatings->isNotEmpty()
                ? (float) number_format(max($employeeRatings->avg('rating'), 0), 2)
                : 0,
            'is_available' => (bool) $this->is_available,
            'designation' => $this->designation,
            'employee_type' => optional($this->handymantype)->name,
            'known_languages' => $this->known_languages,
            'skills' => $this->skills,
            'why_choose_me' => $this->why_choose_me,
        ];
    }
}
