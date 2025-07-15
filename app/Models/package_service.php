<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class package_service extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'service_id',
        'package_id',
        'service_type_data' ,
        'count' ,
        'usage_times',
        'duration_of_use' ,
        'price' ,
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
