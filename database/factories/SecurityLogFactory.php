<?php

namespace Database\Factories;

use App\Models\SecurityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SecurityLog>
 */
class SecurityLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event' => SecurityLog::EVENT_LOGIN_SUCCESS,
            'user_id' => User::factory(),
            'attempted_email' => null,
            'ip_address' => '10.0.0.' . fake()->numberBetween(2, 254),
            'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64) Chrome/124.0',
            'metadata' => null,
        ];
    }

    public function failed(?string $email = null): static
    {
        return $this->state(fn () => [
            'event' => SecurityLog::EVENT_LOGIN_FAILED,
            'user_id' => null,
            'attempted_email' => $email ?? fake()->safeEmail(),
        ]);
    }
}
