<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Region extends Model
{
    use HasFactory;
    protected $table = 'region';
    protected $fillable = ['name','city_id','time_id','phone'];

    public function cities(): BelongsTo
    {
        return $this->belongsTo(CityRegion::class, 'city_id');
    }
}
