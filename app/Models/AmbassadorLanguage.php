<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AmbassadorLanguage extends Model
{
    use HasFactory;

    protected $table = 'user_languages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'language_id',
        'language_level_id',
    ];

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function ambassador(): BelongsTo
    {
        return $this->belongsTo(Ambassador::class, 'user_id', 'id');
    }
}
