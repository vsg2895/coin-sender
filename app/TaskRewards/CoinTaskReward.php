<?php

namespace App\TaskRewards;

use Brick\Math\BigDecimal;
use App\Models\{Task, Ambassador};
use App\Contracts\TaskRewardContract;

class CoinTaskReward extends BaseTaskReward implements TaskRewardContract
{
    public function giveTo(Ambassador $ambassador, Task $task)
    {
        $coins = BigDecimal::of($this->taskReward->value);
        if ($task->level_coefficient) {
            $coins = $coins->multipliedBy(config('levels.coefficients')[$ambassador->level]);
        }

        $userWallet = $ambassador->wallets()->firstOrCreate([
            'coin_type_id' => $task->coin_type_id,
        ], [
            'address' => '',
            'balance' => 0,
        ]);

        $userWallet->balance = (string) $coins->plus($userWallet->balance);
        $userWallet->save();

        $ambassador->historyWallets()->create([
            'value' => (string) $coins,
            'points' => $this->rating,
            'task_id' => $task->id,
            'user_wallet_id' => $userWallet->id,
        ]);
    }
}
