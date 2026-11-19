<?php

namespace App\Contracts;

use App\Models\DeviceToken;

interface PushSender
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function send(DeviceToken $token, array $payload): void;
}
