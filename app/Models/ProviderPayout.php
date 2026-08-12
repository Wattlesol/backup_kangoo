<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderPayout extends Model
{
    use HasFactory;
    protected $table = 'provider_payouts';
    protected $fillable = [
        'provider_id', 'payment_method', 'description','amount','status','paid_date','bank_id',
    ];
    protected $casts = [
        'provider_id'     => 'integer',
        'amount'    => 'double',
    ];
    public function providers(){
        return $this->belongsTo(User::class, 'provider_id','id');
    }
    public function scopeMyPayout($query)
    {
        $user = auth()->user();

        if($user->hasAnyRole(['admin', 'demo_admin']) || in_array($user->user_type, ['admin', 'demo_admin'], true)) {
            return $query;
        }

        if($user->hasRole('provider') || $user->user_type === 'provider') {
            return $query->where('provider_id', $user->id);
        }

        return $query->whereRaw('1 = 0');
    }

}
