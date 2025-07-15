<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UsersFeedback extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'booking_id',
        'subscription_id',
        'rate',
        'feedback',
    ];
}
