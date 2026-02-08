<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class LinePushService
{
    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function pushText(string $to, string $text): void
    {
        $token = config('services.line_message.channel_access_token');

        Http::withToken($token)
            ->post('https://api.line.me/v2/bot/message/push', [
                'to' => $to,
                'messages' => [
                    ['type' => 'text', 'text' => $text],
                ],
            ])
            ->throw(); // 非 2xx 直接丟 exception
    }
}
