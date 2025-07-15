<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PackageComplaint extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'subscription_id',
        'complaint_type',
        'complaint_details',
        'service_id',
        'booking_id',
        'file',
    ];


    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(ServicePacakgeSubscription::class, 'subscription_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
    public function package_service_booking(): BelongsTo
    {
        return $this->belongsTo(package_service_booking::class, 'booking_id');
    }

    public function complaints_comment(): HasMany
    {
        return $this->hasMany(complaints_comment::class,'complaint_id');
    }
}
