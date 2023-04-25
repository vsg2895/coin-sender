<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activity extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
    ];

    public function links(): HasMany
    {
        return $this->hasMany(ActivityLink::class);
    }

    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class, 'activity_id', 'id');
    }
}
