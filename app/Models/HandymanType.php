<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HandymanType extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'handyman_types';
    protected $fillable = [
        'name', 'normalized_name', 'active_normalized_name', 'commission', 'status','type'
    ];

    protected $casts = [
        'commission'=> 'double',
        'status'    => 'integer',
    ];

    public function setNameAttribute($value)
    {
        $name = trim((string) $value);
        $normalizedName = mb_strtolower($name);

        $this->attributes['name'] = $name;
        $this->attributes['normalized_name'] = $normalizedName;
        $this->attributes['active_normalized_name'] = $normalizedName;
    }
    
}
