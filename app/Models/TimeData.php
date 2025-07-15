<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimeData extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'time_id',
        'day',
        'start_at',
        'end_at',
        'off',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];
}
