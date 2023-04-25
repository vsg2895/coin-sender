<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject, HasMedia
{
    use HasRoles, HasApiTokens, HasFactory, Notifiable, InteractsWithMedia;

    protected $table = 'managers';

    const TYPE_CREATED = 'created';
    const TYPE_REGISTERED = 'registered';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * A model may have multiple roles.
     */
    public function roles(): BelongsToMany
    {
        $relation = $this->morphToMany(
            config('permission.models.role'),
            'model',
            config('permission.table_names.model_has_roles'),
            config('permission.column_names.model_morph_key'),
            PermissionRegistrar::$pivotRole
        );

        if (! PermissionRegistrar::$teams) {
            return $relation;
        }

        return $relation->wherePivot(PermissionRegistrar::$teamsKey, getPermissionsTeamId() ?? 0)
            ->where(function ($q) {
                $teamField = config('permission.table_names.roles').'.'.PermissionRegistrar::$teamsKey;
                $q->whereNull($teamField)->orWhere($teamField, getPermissionsTeamId());
            });
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'manager_id', 'id');
    }

    public function socialProviders(): MorphMany
    {
        return $this->morphMany(SocialProvider::class, 'model');
    }

    public function checkedTasks(): HasMany
    {
        return $this->hasMany(AmbassadorTask::class, 'manager_id', 'id')->where('status', AmbassadorTask::STATUS_DONE);
    }

    public function country(): HasOne
    {
        return $this->hasOne(UserCountry::class, 'manager_id', 'id');
    }

    public function allRoles(): BelongsToMany
    {
        return $this->morphToMany(
            config('permission.models.role'),
            'model',
            config('permission.table_names.model_has_roles'),
            config('permission.column_names.model_morph_key'),
            PermissionRegistrar::$pivotRole
        );
    }

    /**
     * Get the entity's notifications.
     *
     * @return MorphMany
     */
    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable')->orderBy('created_at', 'desc');
    }

    public function languages(): HasMany
    {
        return $this->hasMany(UserLanguage::class, 'manager_id', 'id');
    }

    public function invitation(): MorphMany
    {
        return $this->morphMany(Invitation::class, 'userable')->latest();
    }

    public function socialLinks(): HasMany
    {
        return $this->hasMany(UserSocialLink::class, 'manager_id', 'id');
    }

    public function selfProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'id', 'owner_id')->select(['id', 'name']);
    }

    public function projectMembers(): MorphMany
    {
        return $this->morphMany(ProjectMember::class, 'userable');
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * The channels the user receives notification broadcasts on.
     *
     * @return string
     */
    public function receivesBroadcastNotificationsOn(): string
    {
        return 'manager.'.$this->id;
    }
}
