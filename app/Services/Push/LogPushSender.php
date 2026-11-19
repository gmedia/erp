<?php

namespace App\Services\Push;

use App\Contracts\PushSender;
use App\Models\DeviceToken;
use Illuminate\Support\Facades\Log;

class LogPushSender implements PushSender
{
    public function send(DeviceToken $token, array $payload): void
    {
        Log::info('push.notification', [
            'device_token_id' => $token->id,
            'platform' => $token->platform,
            'payload' => $payload,
        ]);
    }
}
