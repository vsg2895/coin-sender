<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AmbassadorActivityLink extends Model
{
    use HasFactory;

    protected $table = 'user_activity_links';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'content',
        'user_id',
        'activity_link_id',
    ];

    public function link(): BelongsTo
    {
        return $this->belongsTo(ActivityLink::class, 'activity_link_id', 'id');
    }

    public function ambassador(): BelongsTo
    {
        return $this->belongsTo(Ambassador::class, 'user_id', 'id');
    }
}
