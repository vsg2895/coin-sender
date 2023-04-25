<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmbassadorTaskAssignment extends Model
{
    use HasFactory;

    protected $table = 'user_task_assignments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'task_id',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function ambassador()
    {
        return $this->belongsTo(Ambassador::class, 'user_id', 'id');
    }
}
