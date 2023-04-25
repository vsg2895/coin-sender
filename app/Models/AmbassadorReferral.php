<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AmbassadorReferral extends Model
{
    use HasFactory;

    protected $table = 'user_referrals';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'task_id',
        'user_id',
        'referral_id',
        'user_task_id',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function referral(): BelongsTo
    {
        return $this->belongsTo(Ambassador::class, 'referral_id', 'id');
    }

    public function ambassador(): BelongsTo
    {
        return $this->belongsTo(Ambassador::class, 'user_id', 'id');
    }

    public function ambassadorTask(): BelongsTo
    {
        return $this->belongsTo(AmbassadorTask::class, 'user_task_id', 'id');
    }
}
