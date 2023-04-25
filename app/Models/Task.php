<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Task extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = [
        'rewards',
        'project',
        'activity',
        'coinType',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'text',
        'priority',
        'manager_id',
        'project_id',
        'activity_id',
        'coin_type_id',
        'verifier_driver',
        'min_level',
        'max_level',
        'started_at',
        'ended_at',
        'number_of_winners',
        'number_of_invites',
        'level_coefficient',
        'number_of_participants',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'level_coefficient' => 'boolean',
    ];

    protected $appends = [
        'autovalidate',
        'status_by_dates',
        'is_invite_friends',
        'editing_not_available',
    ];

    public function rewards()
    {
        return $this->hasMany(TaskReward::class);
    }

    public function referrals()
    {
        return $this->hasMany(AmbassadorReferral::class, 'task_id', 'id');
    }

    public function conditions()
    {
        return $this->hasMany(TaskCondition::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id', 'id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function coinType()
    {
        return $this->belongsTo(CoinType::class);
    }

    public function verifier()
    {
        return $this->hasOne(TaskVerifier::class, 'task_id', 'id');
    }

    public function ambassadorTasks()
    {
        return $this->hasMany(AmbassadorTask::class);
    }

    public function ambassadorAssignments()
    {
        return $this->belongsToMany(
            Ambassador::class,
            'user_task_assignments',
            'task_id',
            'user_id',
        );
    }

    public function ambassadorTasksInWork()
    {
        return $this->hasMany(AmbassadorTask::class)->inWork();
    }

    public function ambassadorTasksCompleted()
    {
        return $this->hasMany(AmbassadorTask::class)->where('status', AmbassadorTask::STATUS_DONE);
    }

    public function getIsInviteFriendsAttribute()
    {
        return $this->number_of_winners > 0 || $this->number_of_invites > 0;
    }

    public function getAutovalidateAttribute()
    {
        return !empty($this->verifier_driver);
    }

    public function getStatusByDatesAttribute()
    {
        $now = now();
        $status = 'finished';

        if ($this->started_at > $now) {
            $status = 'upcoming';
        } else if ($this->started_at < $now && $this->ended_at > $now->subDays(1)) {
            $status = 'available';
        }

        return $status;
    }

    public function setEndedAtAttribute($value)
    {
        $this->attributes['ended_at'] = Carbon::createFromTimestamp($value);
    }

    public function setStartedAtAttribute($value)
    {
        $this->attributes['started_at'] = Carbon::createFromTimestamp($value);
    }

    public function getEditingNotAvailableAttribute()
    {
        return $this->ambassador_tasks_count > 0 || $this->status_by_dates === 'finished';
    }
}
