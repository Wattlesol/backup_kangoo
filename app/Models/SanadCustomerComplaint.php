<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class SanadCustomerComplaint extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'booking_id',
        'customer_id',
        'complaint_type',
        'description',
        'priority',
        'status',
        'resolution_notes',
        'resolved_at',
    ];

    protected $casts = [
        'booking_id' => 'integer',
        'customer_id' => 'integer',
        'resolved_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id', 'id')->withTrashed();
    }
}
