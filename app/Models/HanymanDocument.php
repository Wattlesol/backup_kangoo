<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HanymanDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'handyman_id',
        'expired_date',
        'file',
        'note',
        'name',
    ];

    protected $casts = [
        'expired_date' => 'date',
    ];
}
