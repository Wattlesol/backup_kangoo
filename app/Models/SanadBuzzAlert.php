<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SanadBuzzAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'sender_id',
        'recipient_id',
        'recipient_role',
        'priority',
        'status',
        'message',
        'reply_count',
        'last_reply_at',
        'acknowledged_at',
    ];

    protected $casts = [
        'booking_id' => 'integer',
        'sender_id' => 'integer',
        'recipient_id' => 'integer',
        'reply_count' => 'integer',
        'last_reply_at' => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'id');
    }

    public function replies()
    {
        return $this->hasMany(SanadChatMessage::class, 'buzz_alert_id', 'id')
            ->where('message_type', 'buzz_reply')
            ->orderBy('created_at');
    }
}
