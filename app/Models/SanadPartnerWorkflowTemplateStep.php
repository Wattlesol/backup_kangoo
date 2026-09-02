<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SanadPartnerWorkflowTemplateStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_template_id',
        'stage_name',
        'role',
        'execution_order',
        'parallel_group',
        'estimated_duration_minutes',
        'required_skills',
    ];

    protected $casts = [
        'workflow_template_id' => 'integer',
        'execution_order' => 'integer',
        'parallel_group' => 'integer',
        'estimated_duration_minutes' => 'integer',
        'required_skills' => 'array',
    ];

    public function template()
    {
        return $this->belongsTo(SanadPartnerWorkflowTemplate::class, 'workflow_template_id');
    }
}
