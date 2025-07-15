<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Qualitycontrol extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'provider_id',
        'status',
        'title',
        'created_by',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class,'provider_id');
    }
    public function createdby(): BelongsTo
    {
        return $this->belongsTo(User::class,'created_by');
    }

    public function complaints_comment(): HasMany
    {
        return $this->hasMany(QualitycontrolComment::class,'quality_control_id');
    }
}
