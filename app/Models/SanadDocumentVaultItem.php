<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SanadDocumentVaultItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'booking_id',
        'owner_id',
        'uploaded_by',
        'document_type',
        'verification_status',
        'visible_to',
        'file_name',
        'file_path',
        'approved_at',
        'approved_by',
        'retention_until',
    ];

    protected $casts = [
        'booking_id' => 'integer',
        'owner_id' => 'integer',
        'uploaded_by' => 'integer',
        'visible_to' => 'array',
        'approved_at' => 'datetime',
        'approved_by' => 'integer',
        'retention_until' => 'datetime',
    ];
}
