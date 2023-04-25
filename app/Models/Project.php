<?php

namespace App\Models;

use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Project extends Model implements HasMedia
{
    use HasFactory;
    use Notifiable;
    use InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'public',
        'owner_id',
        'description',
        'pool_amount',
        'coin_type_id',
        'blockchain_id',
        'medium_username',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'public' => 'boolean',
    ];

    public static function boot()
    {
        parent::boot();

        self::created(function ($model) {
            $currentTeamId = getPermissionsTeamId();
            setPermissionsTeamId($model);
            User::find(1)->assignRole('Super Admin');
            setPermissionsTeamId($currentTeamId);
        });

        self::addGlobalScope(function ($query) {
            $user = auth()->user();
            if ($user && !$user->hasRole('Super Admin')) {
                $query->whereIn('id', $user->projectMembers->pluck('project_id')->toArray());
            }
        });
    }

    public function tags(): HasMany
    {
        return $this->hasMany(ProjectTag::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id', 'id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->without('project');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(AmbassadorProjectReport::class, 'project_id', 'id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function coinType(): BelongsTo
    {
        return $this->belongsTo(CoinType::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function blockchain(): BelongsTo
    {
        return $this->belongsTo(Blockchain::class);
    }

    public function socialLinks(): HasMany
    {
        return $this->hasMany(ProjectSocialLink::class);
    }

    public function socialProviders(): MorphMany
    {
        return $this->morphMany(SocialProvider::class, 'model');
    }

    public function showcaseTasks(): HasMany
    {
        return $this->hasMany(Task::class)->where('ended_at', '>', now()->subDays(1))
            ->orderByDesc('created_at')
            ->limit(6);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo');
        $this->addMediaCollection('banner');
    }

    public function getPoolAmountAttribute($poolAmount)
    {
        return BigDecimal::of($poolAmount ?? 0);
    }

    public function setPoolAmountAttribute($poolAmount)
    {
        $this->attributes['pool_amount'] = (string) BigDecimal::of($poolAmount);
    }

    public function discordProvider(): ?SocialProvider
    {
        return $this->socialProviders->where('provider_name', 'discord_bot')->first();
    }

    public function telegramProvider(): ?SocialProvider
    {
        return $this->socialProviders->where('provider_name', 'telegram_bot')->first();
    }

    public function routeNotificationForDiscord()
    {
        $socialProvider = $this->discordProvider();
        return $socialProvider?->provider_id;
    }

    public function routeNotificationForTelegram()
    {
        $socialProvider = $this->telegramProvider();
        return $socialProvider?->provider_id;
    }
}
