<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\DatabaseNotification;

class Notification extends DatabaseNotification
{
    protected $appends = [
        'invitation_status',
    ];

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class, 'invitation_token', 'token');
    }

    public function getInvitationStatusAttribute()
    {
        return optional($this->invitation)->status;
    }
}
