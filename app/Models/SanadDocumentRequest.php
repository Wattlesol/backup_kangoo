<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SanadDocumentRequest extends Model
{
    protected $fillable = [
        'booking_id','service_id','document_key','document_name','requested_from',
        'requested_from_user_id','requested_by','reason','instructions','required',
        'due_at','status','document_id','reviewed_by','reviewed_at','review_reason',
    ];

    protected $casts = [
        'required' => 'boolean', 'due_at' => 'date', 'reviewed_at' => 'datetime',
    ];

    public function booking() { return $this->belongsTo(Booking::class); }
    public function service() { return $this->belongsTo(Service::class); }
    public function document() { return $this->belongsTo(SanadDocumentVaultItem::class, 'document_id'); }
    public function requester() { return $this->belongsTo(User::class, 'requested_by'); }
}
