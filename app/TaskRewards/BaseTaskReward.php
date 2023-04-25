<?php

namespace App\TaskRewards;

use App\Models\TaskReward;

class BaseTaskReward
{
    /**
     * @param TaskReward $taskReward
     */
    public function __construct(
        protected TaskReward $taskReward,
        protected int $rating,
    )
    {
    }
}
