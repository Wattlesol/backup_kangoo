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
        'category',
        'content',
        'visible_to',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'visible_to' => 'array',
        'is_active' => 'boolean',
        'created_by' => 'integer',
    ];
}
