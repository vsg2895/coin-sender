<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Ambassador extends Authenticatable implements JWTSubject, HasMedia
{
    use HasRoles;
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use InteractsWithMedia;

    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'nonce',
        'email',
        'phone',
        'level',
        'wallet',
        'points',
        'password',
        'total_points',
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
        'verified_at' => 'datetime'
    ];

    public function wallets(): HasMany
    {
        return $this->hasMany(AmbassadorWallet::class, 'user_id', 'id');
    }

    public function historyWallets(): HasMany
    {
        return $this->hasMany(AmbassadorWalletHistory::class, 'user_id', 'id');
    }

    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(AmbassadorWalletWithdrawalRequest::class, 'user_id', 'id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(AmbassadorTask::class, 'user_id', 'id');
    }

    public function tasksInWork(): HasMany
    {
        return $this->hasMany(AmbassadorTask::class, 'user_id', 'id')->inWork();
    }

    public function tasksIsDone(): HasMany
    {
        return $this->hasMany(AmbassadorTask::class, 'user_id', 'id')->where('status', AmbassadorTask::STATUS_DONE);
    }

    public function skills(): HasMany
    {
        return $this->hasMany(AmbassadorSkill::class, 'user_id', 'id');
    }

    public function links(): HasMany
    {
        return $this->hasMany(AmbassadorActivityLink::class, 'user_id', 'id');
    }

    public function country(): HasOne
    {
        return $this->hasOne(AmbassadorCountry::class, 'user_id', 'id');
    }

    public function languages(): HasMany
    {
        return $this->hasMany(AmbassadorLanguage::class, 'user_id', 'id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(AmbassadorActivity::class, 'user_id', 'id');
    }

    public function invitation(): MorphMany
    {
        return $this->morphMany(Invitation::class, 'userable')->latest();
    }

    public function projectMembers(): MorphMany
    {
        return $this->morphMany(ProjectMember::class, 'userable');
    }

    public function socialLinks(): HasMany
    {
        return $this->hasMany(AmbassadorSocialLink::class, 'user_id', 'id');
    }

    public function socialProviders(): MorphMany
    {
        return $this->morphMany(SocialProvider::class, 'model');
    }

    public function levelPoints(): HasMany
    {
        return $this->hasMany(AmbassadorLevelPoint::class, 'user_id', 'id');
    }

    public function activityLinks(): HasMany
    {
        return $this->hasMany(AmbassadorActivityLink::class, 'user_id', 'id');
    }

    public function getNameAttribute()
    {
        return $this->attributes['name'] ?? 'Talent'.$this->id;
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
        return 'ambassador.'.$this->id;
    }
}
