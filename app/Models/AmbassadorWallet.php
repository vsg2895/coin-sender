<?php

namespace App\Models;

use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmbassadorWallet extends Model
{
    use HasFactory;

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = [
        'coinType',
    ];

    protected $table = 'user_wallets';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'address',
        'balance',
        'user_id',
        'is_primary',
        'coin_type_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function coinType()
    {
        return $this->belongsTo(CoinType::class);
    }

    public function ambassador()
    {
        return $this->belongsTo(Ambassador::class, 'user_id', 'id');
    }

    public function getBalanceAttribute($balance)
    {
        return BigDecimal::of($balance);
    }

    public function setBalanceAttribute($balance)
    {
        $this->attributes['balance'] = (string) BigDecimal::of($balance);
    }
}
