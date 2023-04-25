<?php

namespace App\Models;

use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmbassadorWalletWithdrawalRequest extends Model
{
    use HasFactory;

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = [
        'wallet',
    ];

    protected $table = 'user_wallet_withdrawal_requests';

    const STATUS_PENDING = 'pending';
    const STATUS_CANCELED = 'canceled';
    const STATUS_EXECUTED = 'executed';
    const STATUS_ACCEPTED = 'accepted';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'value',
        'status',
        'tx_hash',
        'user_id',
        'user_wallet_id',
    ];

    public function wallet()
    {
        return $this->belongsTo(AmbassadorWallet::class, 'user_wallet_id', 'id');
    }

    public function ambassador()
    {
        return $this->belongsTo(Ambassador::class, 'user_id', 'id');
    }

    public function getValueAttribute($value)
    {
        return BigDecimal::of($value);
    }

    public function setValueAttribute($value)
    {
        $this->attributes['value'] = (string) BigDecimal::of($value);
    }
}
