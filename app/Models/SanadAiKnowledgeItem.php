<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SanadAiKnowledgeItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'title_ar',
        'category',
        'category_ar',
        'content',
        'content_ar',
        'visible_to',
        'metadata',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'visible_to' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'created_by' => 'integer',
    ];

    public function chunks()
    {
        return $this->hasMany(SanadAiKnowledgeChunk::class, 'knowledge_item_id');
    }
}
