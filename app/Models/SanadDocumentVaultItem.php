<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;

class SanadDocumentVaultItem extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'booking_id',
        'service_id',
        'provider_id',
        'owner_id',
        'uploaded_by',
        'document_type',
        'verification_status',
        'visible_to',
        'file_name',
        'file_path',
        'approved_at',
        'approved_by',
        'review_reason',
        'reviewed_at',
        'reviewed_by',
        'document_key',
        'required',
        'source',
        'retention_until',
    ];

    protected $casts = [
        'booking_id' => 'integer',
        'service_id' => 'integer',
        'provider_id' => 'integer',
        'owner_id' => 'integer',
        'uploaded_by' => 'integer',
        'visible_to' => 'array',
        'approved_at' => 'datetime',
        'approved_by' => 'integer',
        'reviewed_by' => 'integer',
        'required' => 'boolean',
        'reviewed_at' => 'datetime',
        'retention_until' => 'datetime',
    ];

    public function booking() { return $this->belongsTo(Booking::class); }
    public function service() { return $this->belongsTo(Service::class); }
    public function provider() { return $this->belongsTo(User::class, 'provider_id'); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }
}
