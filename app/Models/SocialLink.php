<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class SocialLink extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    const ASSIGNED_TO_PROJECT = 'project';
    const ASSIGNED_TO_AMBASSADOR = 'ambassador';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'order',
        'assigned_to',
    ];
}
