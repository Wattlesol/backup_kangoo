<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionCar extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'car_number',
        'car_year',
        'car_model',
        'subscription_id',
    ];
}
