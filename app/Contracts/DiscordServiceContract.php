<?php

namespace App\Contracts;

interface DiscordServiceContract
{
    public function giveRole(string $roleId, string $guildId, string $memberId): bool;
    public function getGuild(string $id): array;
    public function getGuildRoles(string $id): array;
    public function sendMessageToGuild(string $id, array $data): bool;
}
