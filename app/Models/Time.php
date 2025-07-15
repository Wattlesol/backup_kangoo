<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Time extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
    ];

    public function time_data(): HasMany
    {
        return $this->hasMany(TimeData::class,'time_id');
    }
}
