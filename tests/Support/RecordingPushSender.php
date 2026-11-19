<?php

namespace Tests\Support;

use App\Contracts\PushSender;
use App\Models\DeviceToken;

class RecordingPushSender implements PushSender
{
    /** @var list<array{token: DeviceToken, payload: array<string, mixed>}> */
    public array $sent = [];

    public function send(DeviceToken $token, array $payload): void
    {
        $this->sent[] = ['token' => $token, 'payload' => $payload];
    }
}
