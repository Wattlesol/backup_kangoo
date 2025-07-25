<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ServicePackage extends Model implements  HasMedia
{
    use InteractsWithMedia,HasFactory;
    protected $table = 'service_packages';
    protected $fillable = [
        'name', 'description', 'provider_id', 'status' , 'price','start_at','end_at','is_featured','category_id','subcategory_id','package_type','pricelist_id','service_id','car_number','duration'
    ];
    protected $casts = [
        'provider_id'    => 'integer',
        'status'    => 'integer',
        'price'  => 'double',
        'is_featured' => 'integer',
        'category_id' => 'integer',
        'subcategory_id' => 'integer',

    ];

    public function packageServices(){
        return $this->hasMany(PackageServiceMapping::class, 'service_package_id','id');
    }
    public function category(){
        return $this->belongsTo('App\Models\Category','category_id','id');
    }
    public function subcategory(){
        return $this->belongsTo('App\Models\SubCategory','subcategory_id','id');
    }
    public function pricelist(){
        return $this->belongsTo('App\Models\PriceList','pricelist_id','id');
    }
    public function providers(){
        return $this->belongsTo('App\Models\User','provider_id','id');
    }
    public function bookingPackageMappings(){
        return $this->hasMany(BookingPackageMapping::class, 'service_package_id','id');
    }

    public function scopeMyPackage($query)
    {
        if(auth()->user()->hasRole('admin')) {
            return $query;
        }

        if(auth()->user()->hasRole('provider')) {
            return $query->where('provider_id', \Auth::id());
        }

        return $query;
    }

    public function package_service_data(): HasMany
    {
        return $this->hasMany(package_service::class,'package_id');
    }
}
