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
    ];

    protected $casts = [
        'booking_id' => 'integer',
        'participant_roles' => 'array',
        'created_by' => 'integer',
    ];

    public function messages()
    {
        return $this->hasMany(SanadChatMessage::class, 'thread_id', 'id')->orderBy('created_at');
    }
}
