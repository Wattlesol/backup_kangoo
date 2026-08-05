<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SanadChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'thread_id',
        'sender_id',
        'sender_role',
        'message',
        'visible_to',
        'read_at',
    ];

    protected $casts = [
        'thread_id' => 'integer',
        'sender_id' => 'integer',
        'visible_to' => 'array',
        'read_at' => 'datetime',
    ];

    public function thread()
    {
        return $this->belongsTo(SanadChatThread::class, 'thread_id', 'id');
    }
}
