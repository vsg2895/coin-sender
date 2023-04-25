<?php

namespace App\Services;

use App\Contracts\TelegramServiceContract;

use Exception;
use Illuminate\Support\Facades\Http;

class TelegramService implements TelegramServiceContract
{
    public function getChat(string $id): array
    {
        try {
            $url = config('services.telegram.endpoint') . config('services.telegram.client_secret');
            $response = Http::baseUrl($url)->get('getChat', ['chat_id' => $id]);

            $data = $response->json();
            if (empty($data) || !$response->ok()
                || (isset($data['ok']) && !$data['ok'])
                || !isset($data['result'])) {
                return [];
            }

            $result = [
                'id' => (string) $data['result']['id'],
                'title' => $data['result']['title'],
                'username' => $data['result']['username'] ?? null,
            ];
        } catch (Exception) {
            $result = [];
        }

        return $result;
    }
}
