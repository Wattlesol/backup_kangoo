<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class package_service_booking extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'service_id',
        'date',
        'subscription_id',
        'status',
        'handyman_id'
        ,'car_id', 'address_id','provider_id','start_at','end_at'
    ];


    public function address(): BelongsTo
    {
        return $this->belongsTo(SubscriptionAddress::class,'address_id');
    }
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(ServicePacakgeSubscription::class, 'subscription_id');
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(SubscriptionCar::class, 'car_id');
    }

    public function handyman(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handyman_id');
    }

    public function rate()
    {
        return $this->hasMany(HanyManRateingService::class,'booking_id')->where('handyman_id',auth()->user()->id);
    }
    public function UsersFeedback()
    {
        return $this->hasMany(UsersFeedback::class,'booking_id')->where('user_id',auth()->user()->id);
    }

    public function PackageComplaint(): BelongsTo
    {
        return $this->belongsTo(PackageComplaint::class,'id','booking_id');
    }
}
