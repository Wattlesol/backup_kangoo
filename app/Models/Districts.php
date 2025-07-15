<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Districts extends Model
{
    use HasFactory;
    protected $table = 'districts';
    protected $fillable = ['name','region_id'];

    public function cities(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }
}
