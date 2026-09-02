<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SanadPartnerServiceWorkflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'service_id',
        'workflow_template_id',
        'is_default',
    ];

    protected $casts = [
        'provider_id' => 'integer',
        'service_id' => 'integer',
        'workflow_template_id' => 'integer',
        'is_default' => 'boolean',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function template()
    {
        return $this->belongsTo(SanadPartnerWorkflowTemplate::class, 'workflow_template_id');
    }
}
