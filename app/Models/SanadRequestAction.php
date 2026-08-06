<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SanadRequestAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'actor_id',
        'actor_role',
        'action',
        'previous_status',
        'current_status',
        'previous_stage',
        'current_stage',
        'reason',
        'internal_note',
        'metadata',
    ];

    protected $casts = [
        'booking_id' => 'integer',
        'actor_id' => 'integer',
        'metadata' => 'array',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id', 'id')->withTrashed();
    }
}
