<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class complaints_comment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'comment',
        'complaint_id',
        'user_id',
    ];
    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
}
