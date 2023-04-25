<?php

use App\Models\Ambassador;
use Brick\Math\BigDecimal;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\{DB, Http};

if (!function_exists('calculateTaskEstimatedAmount')) {
    function calculateTaskEstimatedAmount(array $data): array
    {
        $min = BigDecimal::of(0);
        $max = BigDecimal::of(0);
        $total = BigDecimal::of(0);

        $minLevel = $data['min_level'] ?? null;
        $maxLevel = $data['max_level'] ?? null;

        $amountInCoins = collect($data['rewards'])->first(function ($reward) {
            return $reward['type'] === 'coins';
        })['value'];

        $assignUserIds = $data['assign_user_ids'] ?? [];
        $levelCoefficients = config('levels.coefficients');
        $coefficientEnabled = $data['level_coefficient'];
        $countAssignUserIds = count($assignUserIds);
        $numberOfAmbassadors = $data['number_of_participants'] ?? $countAssignUserIds;

        if ($countAssignUserIds > 0 && $coefficientEnabled) {
            $total = Ambassador::findMany($assignUserIds)
                ->reduce(function (BigDecimal $result, Ambassador $ambassador) use ($amountInCoins, $levelCoefficients) {
                    return $result->plus(BigDecimal::of($amountInCoins)->multipliedBy($levelCoefficients[$ambassador->level]));
                }, BigDecimal::of(0));
        } elseif ($coefficientEnabled) {
            if ($minLevel !== $maxLevel) {
                $min = BigDecimal::of($amountInCoins)->multipliedBy($numberOfAmbassadors);
                $max = BigDecimal::of($amountInCoins)->multipliedBy($numberOfAmbassadors);

                $min = $min->multipliedBy($levelCoefficients[$minLevel]);
                $max = $max->multipliedBy($levelCoefficients[$maxLevel]);
            } else {
                $total = BigDecimal::of($amountInCoins)->multipliedBy($numberOfAmbassadors);

                $level = is_null($minLevel) ? count($levelCoefficients) - 1 : $minLevel;
                $total = $total->multipliedBy($levelCoefficients[$level]);
            }
        } else {
            $total = BigDecimal::of($amountInCoins)->multipliedBy($numberOfAmbassadors);
        }

        return [
            'min' => $min->toFloat(),
            'max' => $max->toFloat(),
            'total' => $total->toFloat(),
        ];
    }
}

if (!function_exists('getDiscordInvite')) {
    function getDiscordInvite(string|null $code): null|array
    {
        if (!$code) {
            return null;
        }

        return Cache::remember(sprintf('discord_invite_%s', $code), 5 * 60, function () use ($code) {
            try {
                $response = Http::baseUrl(config('services.discord.endpoint'))
                    ->get(sprintf('invite/%s', $code))
                    ->json();
            } catch (Exception) {
                $response = null;
            }

            return $response;
        });
    }
}

if (!function_exists('getDiscordInviteCode')) {
    function getDiscordInviteCode(string $url): string|null
    {
        preg_match('/^(https?:\/\/)?(discord(?:(?:app)?\.com\/invite|\.gg)(?:\/invite)?)\/(?<code>[\w-]{2,255})/i', $url, $discordMatches);
        return $discordMatches['code'] ?? null;
    }
}

if (!function_exists('getTelegramChatId')) {
    function getTelegramChatId(string $url): string|null
    {
        preg_match('/^(https?:\/\/)?(www[.])?(telegram|t)\.me\/(?<chat_id>[a-zA-Z0-9_-]*)\/?/i', $url ?? '', $telegramMatches);
        return $telegramMatches['chat_id'] ?? null;
    }
}

if (!function_exists('getTwitterUsername')) {
    function getTwitterUsername(string $name): string
    {
        return substr($name, 1);
    }
}

if (!function_exists('getTwitterTweetId')) {
    function getTwitterTweetId(string $url): string|null
    {
        preg_match('/^(https?:\/\/)?((www.|m.|mobile.)?twitter\.com)\/(?:#!\/)?(\w+)\/status?\/(?<tweet>\d+)/i', $url ?? '', $tweetMatches);
        return $tweetMatches['tweet'] ?? null;
    }
}

if (!function_exists('getTwitterSpaceId')) {
    function getTwitterSpaceId(string $url): string|null
    {
        preg_match('/^https?:\/\/(www.)?twitter\.com\/i\/spaces?\/(?<space>[a-zA-Z0-9]{1,13})/i', $url ?? '', $spaceMatches);
        return $spaceMatches['space'] ?? null;
    }
}

if (!function_exists('getUserPositionByLevel')) {
    function getUserPositionByLevel($userId, $level): int|null
    {
        return optional(DB::selectOne('SELECT position FROM (SELECT user_id, RANK() OVER (ORDER BY SUM(points) DESC) position FROM user_level_points WHERE level = ? GROUP BY user_id) as t WHERE t.user_id = ?;', [$level, $userId]))->position;
    }
}
