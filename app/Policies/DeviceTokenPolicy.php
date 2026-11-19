<?php

namespace App\Policies;

use App\Models\DeviceToken;
use App\Models\User;

class DeviceTokenPolicy
{
    public function delete(User $user, DeviceToken $deviceToken): bool
    {
        return $deviceToken->user_id === $user->id;
    }
}
