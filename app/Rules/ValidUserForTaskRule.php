<?php

namespace App\Rules;

use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Validation\Rule;

class ValidUserForTaskRule implements Rule
{
    private ?int $minLevel;
    private ?int $maxLevel;
    private ?int $activityId;

    /**
     * Create a new rule instance.
     *
     * @param int|null $minLevel
     * @param int|null $maxLevel
     * @param int|null $activityId
     */
    public function __construct(?int $minLevel, ?int $maxLevel, ?int $activityId)
    {
        $this->minLevel = $minLevel;
        $this->maxLevel = $maxLevel;
        $this->activityId = $activityId;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $query = DB::table('users')
            ->where('users.id', $value);

        if (is_int($this->minLevel) && is_int($this->maxLevel)) {
            $query = $query->where('users.level', '>=', $this->minLevel)
                ->where('users.level', '<=', $this->maxLevel);
        }

        if ($this->activityId) {
            $query = $query->join('user_activities', 'user_activities.user_id', 'users.id')
                ->where('user_activities.activity_id', $this->activityId)
                ->where('user_activities.status', 'approved');
        }

        return $query->count() > 0;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'The :attribute must be contain valid user for task.';
    }
}
