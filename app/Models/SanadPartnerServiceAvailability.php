<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SanadPartnerServiceAvailability extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'service_id',
        'is_enabled',
        'availability',
        'estimated_execution_time',
        'required_employee_skills',
        'internal_notes',
    ];

    protected $casts = [
        'provider_id' => 'integer',
        'service_id' => 'integer',
        'is_enabled' => 'boolean',
        'required_employee_skills' => 'array',
    ];

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id')->withTrashed();
    }
}

