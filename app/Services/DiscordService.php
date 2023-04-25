<?php

namespace App\Services;

use App\Contracts\DiscordServiceContract;

use Exception;
use Illuminate\Support\Facades\Http;

class DiscordService implements DiscordServiceContract
{
    public function giveRole(string $roleId, string $guildId, string $memberId): bool
    {
        try {
            $response = Http::baseUrl(config('services.discord_bot.endpoint'))
                ->post(sprintf('guilds/%s/add-role', $guildId), [
                    'roleId' => $roleId,
                    'memberId' => $memberId,
                ]);

            $data = $response->json();
            if (empty($data) || !$response->ok()) {
                return false;
            }

            return true;
        } catch (Exception) {
            return false;
        }
    }

    public function getGuild(string $id): array
    {
        try {
            $response = Http::baseUrl(config('services.discord_bot.endpoint'))
                ->get(sprintf('guilds/%s', $id));

            $data = $response->json();
            if (empty($data) || !$response->ok()) {
                return [];
            }

            return [
                'id' => $data['id'],
                'name' => $data['name'],
                'roles' => $data['roles'],
                'channels' => $data['channels'],
            ];
        } catch (Exception) {
            return [];
        }
    }

    public function getGuildRoles(string $id): array
    {
        try {
            $response = Http::baseUrl(config('services.discord_bot.endpoint'))
                ->get(sprintf('guilds/%s/roles', $id));

            $data = $response->json();
            if (empty($data) || !$response->ok()) {
                return [];
            }

            return $data;
        } catch (Exception) {
            return [];
        }
    }

    public function sendMessageToGuild(string $id, array $data): bool
    {
        try {
            $response = Http::baseUrl(config('services.discord_bot.endpoint'))
                ->post(sprintf('guilds/%s/messages', $id), $data);

            return $response->ok();
        } catch (Exception) {
            return false;
        }
    }
}
