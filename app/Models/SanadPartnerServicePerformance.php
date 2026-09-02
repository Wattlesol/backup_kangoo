<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SanadPartnerServicePerformance extends Model
{
    protected $table = 'sanad_partner_service_performances';

    protected $fillable = [
        'provider_id',
        'service_id',
        'quality_score',
        'sla_compliance_rate',
        'acceptance_rate',
        'cancellation_rate',
        'average_completion_minutes',
        'completed_orders',
        'last_activity_at',
    ];

    protected $casts = [
        'quality_score' => 'decimal:2',
        'sla_compliance_rate' => 'decimal:2',
        'acceptance_rate' => 'decimal:2',
        'cancellation_rate' => 'decimal:2',
        'average_completion_minutes' => 'decimal:2',
        'last_activity_at' => 'datetime',
    ];

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
