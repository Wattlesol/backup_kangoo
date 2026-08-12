<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SanadPartnerWorkflowStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'workflow_template_id',
        'employee_id',
        'stage_name',
        'role',
        'execution_order',
        'parallel_group',
        'estimated_duration_minutes',
        'depends_on_stage_ids',
        'assignment_mode',
        'status',
        'started_at',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'booking_id' => 'integer',
        'workflow_template_id' => 'integer',
        'employee_id' => 'integer',
        'execution_order' => 'integer',
        'parallel_group' => 'integer',
        'estimated_duration_minutes' => 'integer',
        'depends_on_stage_ids' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function template()
    {
        return $this->belongsTo(SanadPartnerWorkflowTemplate::class, 'workflow_template_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id')->withTrashed();
    }
}
