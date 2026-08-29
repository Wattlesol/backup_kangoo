<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Documents extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'documents';
    protected $fillable = [
        'name', 'name_ar', 'status','is_required'
    ];

    protected $casts = [
        'status'     => 'integer',
        'is_required'    => 'integer',
    ];

    public function providerDocument(){
        return $this->hasMany(ProviderDocument::class, 'document_id','id');
    }

    public function getLocalizedNameAttribute()
    {
        if (app()->getLocale() === 'ar' && filled($this->name_ar)) {
            return $this->name_ar;
        }

        return $this->name;
    }
    
}
