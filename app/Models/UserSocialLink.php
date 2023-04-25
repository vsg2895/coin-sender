<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSocialLink extends Model
{
    use HasFactory;

    protected $table = 'manager_social_links';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'content',
        'manager_id',
        'social_link_id',
    ];

    public function link(): BelongsTo
    {
        return $this->belongsTo(SocialLink::class, 'social_link_id', 'id');
    }
}
