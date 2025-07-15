<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceCity extends Model
{
    use HasFactory;
    protected $table = 'price_list_city';
    protected $fillable = ['price','city_id','price_list_id'];

    public function cities(): BelongsTo
    {
        return $this->belongsTo(Region::class,'city_id');
    }

    public function prices(): BelongsTo
    {
        return $this->belongsTo(PriceList::class,'price_list_id');
    }
}
