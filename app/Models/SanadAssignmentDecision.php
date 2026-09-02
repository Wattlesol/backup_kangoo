<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SanadAssignmentDecision extends Model
{
    protected $fillable = [
        'booking_id', 'recommended_provider_id', 'selected_provider_id',
        'assignment_mode', 'status', 'reason', 'score_snapshot', 'decided_by',
    ];

    protected $casts = ['score_snapshot' => 'array', 'decided_by' => 'integer'];

    public function booking() { return $this->belongsTo(Booking::class); }
    public function recommendedProvider() { return $this->belongsTo(User::class, 'recommended_provider_id'); }
    public function selectedProvider() { return $this->belongsTo(User::class, 'selected_provider_id'); }
    public function actor() { return $this->belongsTo(User::class, 'decided_by'); }
}
