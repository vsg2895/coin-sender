<?php

namespace App\Models;

use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmbassadorWalletHistory extends Model
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

    protected $table = 'user_wallet_history';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'value',
        'points',
        'user_id',
        'task_id',
        'user_wallet_id',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

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
