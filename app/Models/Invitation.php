<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Permission\Models\Role;

class Invitation extends Model
{
    use HasFactory;

    const STATUS_REVOKED = 'revoked';
    const STATUS_PENDING = 'pending';
    const STATUS_DECLINED = 'declined';
    const STATUS_ACCEPTED = 'accepted';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'token',
        'status',
        'role_name',
        'project_id',
        'userable_id',
        'userable_type',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_name', 'name');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    public function userable(): MorphTo
    {
        return $this->morphTo();
    }
}
