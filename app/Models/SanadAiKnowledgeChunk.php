<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SanadAiKnowledgeChunk extends Model
{
    use HasFactory;

    protected $fillable = [
        'knowledge_item_id',
        'chunk_index',
        'content',
        'embedding',
        'embedding_model',
        'vector_id',
        'metadata',
    ];

    protected $casts = [
        'embedding' => 'array',
        'metadata' => 'array',
    ];

    public function knowledgeItem()
    {
        return $this->belongsTo(SanadAiKnowledgeItem::class, 'knowledge_item_id');
    }
}
