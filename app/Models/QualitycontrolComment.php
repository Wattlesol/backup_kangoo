<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QualitycontrolComment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'quality_control_id',
        'comment',
        'file',
        'created_by',
    ];

    public function qualityControl()
    {
        return $this->belongsTo(Qualitycontrol::class, 'quality_control_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
