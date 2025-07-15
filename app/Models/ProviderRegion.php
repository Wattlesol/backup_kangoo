<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProviderRegion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'provider_id',
        'region_id',
    ];

    public function regiondata(): BelongsTo
    {
        return $this->belongsTo(Region::class,'region_id');
    }
}
