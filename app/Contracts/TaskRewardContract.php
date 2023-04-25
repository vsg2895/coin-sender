<?php

namespace App\Contracts;

use App\Models\{Task, Ambassador};

interface TaskRewardContract
{
    public function giveTo(Ambassador $ambassador, Task $task);
}
