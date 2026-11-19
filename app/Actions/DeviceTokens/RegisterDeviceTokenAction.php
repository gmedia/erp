<?php

namespace App\Actions\DeviceTokens;

use App\Models\DeviceToken;
use App\Models\User;

class RegisterDeviceTokenAction
{
    /**
     * @param  array{token: string, platform: string, device_name?: string|null}  $data
     */
    public function execute(User $user, array $data): DeviceToken
    {
        return DeviceToken::updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id' => $user->id,
                'platform' => $data['platform'],
                'device_name' => $data['device_name'] ?? null,
                'last_seen_at' => now(),
            ],
        );
    }
}
