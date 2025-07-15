<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\EcommerceNotificationTrait;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Store extends BaseModel implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia, EcommerceNotificationTrait;

    protected $table = 'stores';

    protected $fillable = [
        'provider_id',
        'name',
        'description',
        'slug',
        'phone',
        'address',
        'country_id',
        'state_id',
        'city_id',
        'latitude',
        'longitude',
        'status',
        'is_active',
        'business_hours',
        'delivery_radius',
        'minimum_order_amount',
        'delivery_fee',
        'rejection_reason',
        'approved_at',
        'approved_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'business_hours' => 'array',
        'delivery_radius' => 'decimal:2',
        'minimum_order_amount' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'approved_at' => 'datetime'
    ];

    // Relationships
    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function storeProducts()
    {
        return $this->hasMany(StoreProduct::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'store_products')
                    ->withPivot(['store_price', 'stock_quantity', 'is_available'])
                    ->withTimestamps();
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeNearby($query, $latitude, $longitude, $radius = 50)
    {
        return $query->selectRaw("*,
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance",
            [$latitude, $longitude, $latitude])
            ->having('distance', '<', $radius)
            ->orderBy('distance');
    }

    // Accessors
    public function getLogoAttribute()
    {
        return getSingleMedia($this, 'store_logo', null);
    }

    public function getIsOpenAttribute()
    {
        if (!$this->business_hours) {
            return true; // Always open if no hours set
        }

        $currentDay = strtolower(now()->format('l'));
        $currentTime = now()->format('H:i');

        if (isset($this->business_hours[$currentDay])) {
            $hours = $this->business_hours[$currentDay];
            if ($hours['is_open'] ?? false) {
                return $currentTime >= $hours['open'] && $currentTime <= $hours['close'];
            }
        }

        return false;
    }

    // Methods
    /**
     * Approve the store
     */
    public function approve()
    {
        $this->update(['status' => 'approved']);
        $this->sendStoreApprovedNotification($this);
        return $this;
    }

    /**
     * Reject the store
     */
    public function reject($reason = null)
    {
        $this->update(['status' => 'rejected']);
        $this->sendStoreRejectedNotification($this, $reason);
        return $this;
    }

    /**
     * Send application submitted notification
     */
    public function sendApplicationNotification()
    {
        $this->sendStoreApplicationSubmittedNotification($this);
        return $this;
    }
}
