<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HanyManRateingService extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'booking_id',
        'handyman_id',
        'rate',
        'feedback',
        'subscription_id',
    ];


    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }
    public function handyman()
    {
        return $this->belongsTo(User::class, 'handyman_id');
    }
    public function subscription()
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }
}
