<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionAddress extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'subscription_id',
        'city_id',
        'region_id',
        'area_id',
        'address'
    ];


    public function CityData(): BelongsTo
    {
        return $this->belongsTo(CityRegion::class,'city_id');
    }

    public function RegionData(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }
    public function AreaData(): BelongsTo
    {
        return $this->belongsTo(Districts::class, 'area_id');
    }
}
