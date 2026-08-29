<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SanadChatThread extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'thread_type',
        'participant_roles',
        'created_by',
        'status',
        'last_message_at',
        'closed_by',
        'closed_at',
    ];

    protected $casts = [
        'booking_id' => 'integer',
        'participant_roles' => 'array',
        'created_by' => 'integer',
        'last_message_at' => 'datetime',
        'closed_by' => 'integer',
        'closed_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'id');
    }

    public function unreadMessagesFor($userId)
    {
        return $this->messages()->whereNull('read_at')->where('sender_id', '!=', $userId)->count();
    }

    public function messages()
    {
        return $this->hasMany(SanadChatMessage::class, 'thread_id', 'id')->orderBy('created_at');
    }
}
