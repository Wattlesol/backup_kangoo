<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SanadPartnerWorkflowTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'provider_id' => 'integer',
        'is_active' => 'boolean',
    ];

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id')->withTrashed();
    }

    public function steps()
    {
        return $this->hasMany(SanadPartnerWorkflowTemplateStep::class, 'workflow_template_id')->orderBy('execution_order')->orderBy('id');
    }

    public function serviceLinks()
    {
        return $this->hasMany(SanadPartnerServiceWorkflow::class, 'workflow_template_id');
    }
}
