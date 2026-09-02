<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SanadAiInteraction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'booking_id',
        'question',
        'answer',
        'confidence',
        'requires_escalation',
        'status',
        'metadata',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'booking_id' => 'integer',
        'confidence' => 'float',
        'requires_escalation' => 'boolean',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->withTrashed();
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'id')->withTrashed();
    }
}
