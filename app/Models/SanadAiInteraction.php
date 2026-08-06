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
    ];

    protected $casts = [
        'user_id' => 'integer',
        'booking_id' => 'integer',
        'confidence' => 'float',
        'requires_escalation' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->withTrashed();
    }
}
