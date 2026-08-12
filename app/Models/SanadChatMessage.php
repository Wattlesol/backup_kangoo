<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class SanadChatMessage extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'thread_id',
        'sender_id',
        'sender_role',
        'message',
        'visible_to',
        'read_at',
        'recipient_id','message_type','document_request_id',
    ];

    protected $casts = [
        'thread_id' => 'integer',
        'sender_id' => 'integer',
        'visible_to' => 'array',
        'read_at' => 'datetime',
        'recipient_id' => 'integer', 'document_request_id' => 'integer',
    ];

    public function thread()
    {
        return $this->belongsTo(SanadChatThread::class, 'thread_id', 'id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id', 'id')->withTrashed();
    }
}
