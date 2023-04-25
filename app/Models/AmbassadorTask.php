<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class AmbassadorTask extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $table = 'user_tasks';

    const STATUS_DONE = 'done';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_REJECTED = 'rejected';
    const STATUS_RETURNED = 'returned';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_ON_REVISION = 'on_revision';
    const STATUS_WAITING_FOR_REVIEW = 'waiting_for_review';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'status',
        'rating',
        'report',
        'user_id',
        'task_id',
        'notified',
        'manager_id',
        'revised_at',
        'reported_at',
        'completed_at',
        'referral_code',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'notified' => 'boolean',
        'revised_at' => 'datetime',
        'reported_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        self::addGlobalScope(static function ($query) {
            $user = auth()->user();
            if ($user && !$user->hasRole('Super Admin')) {
                $query->whereRelation('task', function ($query) use ($user) {
                    $query->whereIn('project_id', $user->projectMembers->pluck('project_id')->toArray());
                });
            }
        });
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id', 'id');
    }

    public function coinType()
    {
        return $this->belongsTo(CoinType::class);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function referrals()
    {
        return $this->hasMany(AmbassadorReferral::class, 'user_task_id', 'id');
    }

    public function ambassador()
    {
        return $this->belongsTo(Ambassador::class, 'user_id', 'id');
    }

    public function scopeInWork($query)
    {
        return $query->whereIn('status', [
            AmbassadorTask::STATUS_RETURNED,
            AmbassadorTask::STATUS_IN_PROGRESS,
            AmbassadorTask::STATUS_WAITING_FOR_REVIEW,
            AmbassadorTask::STATUS_ON_REVISION,
        ]);
    }
}
