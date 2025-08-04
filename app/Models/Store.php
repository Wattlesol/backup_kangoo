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
        'created_by',
        'created_by_type',
        'store_type',
        'email',
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
        'store_settings',
        'payment_methods',
        'shipping_methods',
        'terms_and_conditions',
        'privacy_policy',
        'return_policy',
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
        'store_settings' => 'array',
        'payment_methods' => 'array',
        'shipping_methods' => 'array',
        'delivery_radius' => 'decimal:2',
        'minimum_order_amount' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'approved_at' => 'datetime'
    ];

    // Relationships
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
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

    // In single-store architecture, all products belong to the main store
    // Products are directly linked to providers via provider_id

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMain($query)
    {
        return $query->where('store_type', 'main');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeNearby($query, $latitude, $longitude, $radius = 50)
    {
        return $query->selectRaw("*,
            (6371 * acos(cos(radians(?))
            * cos(radians(latitude))
            * cos(radians(longitude) - radians(?))
            + sin(radians(?))
            * sin(radians(latitude)))) AS distance", [$latitude, $longitude, $latitude])
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
     * Boot method to add model events
     */
    protected static function boot()
    {
        parent::boot();

        // Prevent creating multiple main stores
        static::creating(function ($store) {
            if ($store->store_type === 'main') {
                $existingMainStore = static::where('store_type', 'main')->first();
                if ($existingMainStore) {
                    throw new \Exception('Only one main store is allowed in single-store architecture.');
                }
            }
        });
    }

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
