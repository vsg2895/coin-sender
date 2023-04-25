<?php

namespace App\Models;

use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskReward extends Model
{
    use HasFactory;

    const TYPE_COINS = 'coins';
    const TYPE_DISCORD_ROLE = 'discord_role';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'value',
        'task_id',
    ];

    protected $appends = [
        'formatted_value',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function getFormattedValueAttribute()
    {
        return $this->type === 'coins' ? BigDecimal::of($this->value) : $this->value;
    }
}
