<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AmbassadorActivity extends Model
{
    use HasFactory;

    const STATUS_CREATED = 'created';
    const STATUS_APPROVED = 'approved';
    const STATUS_DECLINED = 'declined';

    protected $table = 'user_activities';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'status',
        'user_id',
        'activity_id',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function ambassador(): BelongsTo
    {
        return $this->belongsTo(Ambassador::class, 'user_id', 'id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', AmbassadorActivity::STATUS_APPROVED);
    }
}
