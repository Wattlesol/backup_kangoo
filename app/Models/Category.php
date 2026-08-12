<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;

class Category extends BaseModel implements HasMedia 
{
    use HasFactory,HasRoles,InteractsWithMedia,SoftDeletes;
    protected $table = 'categories';
    protected $fillable = [
        'name', 'name_ar', 'name_en', 'description', 'is_featured', 'status' , 'color', 'icon', 'display_order'
    ];
    protected $casts = [
        'status'    => 'integer',
        'is_featured'  => 'integer',
        'display_order'  => 'integer',
    ];

    public function services(){
        return $this->hasMany(Service::class, 'category_id','id');
    }
    public function scopeList($query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }
    
    
}
