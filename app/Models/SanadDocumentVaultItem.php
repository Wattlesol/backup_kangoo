<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        'expiry_date',
        'expiry_reminder_at',
        'expiry_reminder_enabled',
        'expiry_reminder_sent_at',
        'ocr_status',
        'ocr_confidence',
        'ocr_metadata',
        'ocr_processed_at',
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
        'expiry_date' => 'date',
        'expiry_reminder_at' => 'date',
        'expiry_reminder_enabled' => 'boolean',
        'expiry_reminder_sent_at' => 'datetime',
        'ocr_confidence' => 'float',
        'ocr_metadata' => 'array',
        'ocr_processed_at' => 'datetime',
    ];

    public function booking() { return $this->belongsTo(Booking::class); }
    public function service() { return $this->belongsTo(Service::class); }
    public function provider() { return $this->belongsTo(User::class, 'provider_id'); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }

    public function publicDocumentUrl(): ?string
    {
        foreach (['document', 'sanad_document'] as $collection) {
            $media = $this->getFirstMedia($collection);
            if ($media && is_file($media->getPath())) {
                return $media->getUrl();
            }
        }

        if (!$this->file_path) {
            return null;
        }

        if (Str::startsWith($this->file_path, ['http://', 'https://'])) {
            return $this->file_path;
        }

        $relativePath = ltrim(Str::after($this->file_path, '/storage/'), '/');

        return Storage::disk('public')->exists($relativePath)
            ? Storage::disk('public')->url($relativePath)
            : null;
    }
}
