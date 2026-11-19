<?php

namespace App\Actions\DeviceTokens;

use App\Models\DeviceToken;

class RevokeDeviceTokenAction
{
    public function execute(DeviceToken $deviceToken): void
    {
        $deviceToken->delete();
    }
}
