<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceAddon extends BaseModel implements  HasMedia
{
    use InteractsWithMedia,HasFactory,SoftDeletes;
    protected $table = 'service_addons';
    protected $fillable = [
        'name', 'name_ar', 'service_id','price','status','created_by'
    ];
    protected $casts = [
        'service_id'    => 'integer',
        'price'         => 'double',
        'status'        => 'integer',
        'created_by'    => 'integer',
    ];
    public function service(){
        return $this->belongsTo(Service::class,'service_id', 'id');
    }
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'service_addon_category', 'service_addon_id', 'category_id')->withTimestamps();
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'service_addon_service', 'service_addon_id', 'service_id')->withTimestamps();
    }

    public function scopeForService($query, Service $service)
    {
        return $query->where('status', 1)
            ->where(function ($targetQuery) use ($service) {
                $targetQuery
                    ->where(function ($allServicesQuery) {
                        $allServicesQuery->whereNull('service_id')
                            ->doesntHave('services')
                            ->doesntHave('categories');
                    })
                    ->orWhere('service_id', $service->id)
                    ->orWhereHas('services', function ($serviceQuery) use ($service) {
                        $serviceQuery->where('services.id', $service->id);
                    })
                    ->orWhereHas('categories', function ($categoryQuery) use ($service) {
                        $categoryQuery->where('categories.id', $service->category_id);
                    });
            });
    }
    public function scopeList($query)
    {
        return $query->orderBy('deleted_at', 'asc');
    }
    public function scopeServiceAddon($query)
    {
        if(auth()->user()->hasRole('admin')) {

            return $query;
        }

        if (auth()->user()->hasRole('provider')) {
            $user = auth()->user();
            
            if ($user->user_type == 'provider') {
                $providerId = $user->id;
                return $query->where(function ($providerQuery) use ($providerId) {
                    $providerQuery->where('created_by', $providerId)
                        ->orWhereHas('service', function ($query) use ($providerId) {
                            $query->where('provider_id', $providerId);
                        })
                        ->orWhereHas('services', function ($query) use ($providerId) {
                            $query->where('provider_id', $providerId);
                        });
                });
            }
        }
        return $query;
    }
   
}
