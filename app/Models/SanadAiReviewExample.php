<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SanadAiReviewExample extends Model
{
    use HasFactory;

    protected $fillable = [
        'ai_interaction_id',
        'booking_id',
        'reviewed_by',
        'actor_role',
        'question',
        'original_answer',
        'corrected_answer',
        'confidence',
        'review_action',
        'status',
        'context_summary',
        'sources',
        'metadata',
        'promoted_knowledge_item_id',
        'promoted_at',
    ];

    protected $casts = [
        'ai_interaction_id' => 'integer',
        'booking_id' => 'integer',
        'reviewed_by' => 'integer',
        'confidence' => 'float',
        'context_summary' => 'array',
        'sources' => 'array',
        'metadata' => 'array',
        'promoted_knowledge_item_id' => 'integer',
        'promoted_at' => 'datetime',
    ];

    public function interaction()
    {
        return $this->belongsTo(SanadAiInteraction::class, 'ai_interaction_id');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id')->withTrashed();
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by')->withTrashed();
    }

    public function promotedKnowledgeItem()
    {
        return $this->belongsTo(SanadAiKnowledgeItem::class, 'promoted_knowledge_item_id');
    }
}
