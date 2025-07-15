<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServicePacakgeServiceSubscription extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'service_id',
        'service_type_data',
        'count',
        'usage_times',
        'duration_of_use',
        'price',
        'subscription_id',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class,'service_id');
    }
}
