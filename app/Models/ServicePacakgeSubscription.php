<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServicePacakgeSubscription extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'package_id',
        'start_at',
        'end_at',
        'price',
        'package_type',
        'user_id'
        ,'address'
        ,'car_number'
        ,'city_id'
        ,'region_id'
        ,'date_breaks'

    ];

    protected $casts = [
        'start_at' => 'date',
        'end_at' => 'date',
    ];

    public function service(): HasMany
    {
        return $this->hasMany(ServicePacakgeServiceSubscription::class,'subscription_id');
    }

    public function Cars(): HasMany
    {
        return $this->hasMany(SubscriptionCar::class,'subscription_id');
    }
    public function address_data(): HasMany
    {
        return $this->hasMany(SubscriptionAddress::class,'subscription_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function Serverdecimal(): BelongsTo
    {
        return $this->belongsTo(ServicePackage::class,'package_id');
    }
    public function CityData(): BelongsTo
    {
        return $this->belongsTo(CityRegion::class,'city_id');
    }

    public function RegionData(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }
}
