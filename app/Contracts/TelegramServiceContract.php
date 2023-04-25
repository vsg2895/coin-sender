<?php

namespace App\Contracts;

interface TelegramServiceContract
{
    public function getChat(string $id): array;
}
