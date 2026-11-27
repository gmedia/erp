<?php

namespace Database\Seeders;

use App\Actions\SecurityLogs\DetectFailedLoginBurstAction;
use App\Actions\SecurityLogs\PruneSecurityLogsAction;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class AuthenticationSettingSeeder extends Seeder
{
    /**
     * Seed authentication policy settings with neutral defaults.
     */
    public function run(): void
    {
        $defaults = config('social_login.defaults', []);

        $settings = [
            'social_login_enabled' => $defaults['enabled'] ?? true,
            'social_login_jit_provisioning' => $defaults['jit_provisioning'] ?? false,
            'social_login_auto_link_by_email' => $defaults['auto_link_by_email'] ?? false,
            'social_login_allow_personal' => $defaults['allow_personal'] ?? false,
            'social_login_allowed_domains' => $defaults['allowed_domains'] ?? [],
            'social_login_allowed_tenants' => $defaults['allowed_tenants'] ?? [],
            'password_login_enabled' => $defaults['password_login_enabled'] ?? true,
        ];

        $integerSettings = [
            'security_log_retention_days' => PruneSecurityLogsAction::DEFAULT_RETENTION_DAYS,
            'security_log_failed_login_threshold' => DetectFailedLoginBurstAction::DEFAULT_THRESHOLD,
            'security_log_failed_login_window_minutes' => DetectFailedLoginBurstAction::DEFAULT_WINDOW_MINUTES,
        ];

        foreach ($integerSettings as $key => $value) {
            Setting::firstOrCreate(
                ['key' => $key],
                [
                    'group' => 'security',
                    'key' => $key,
                    'value' => (string) $value,
                    'type' => 'integer',
                ]
            );
        }

        foreach ($settings as $key => $value) {
            Setting::firstOrCreate(
                ['key' => $key],
                [
                    'group' => 'authentication',
                    'key' => $key,
                    'value' => is_array($value) ? json_encode($value) : ($value ? '1' : '0'),
                    'type' => is_array($value) ? 'json' : 'boolean',
                ]
            );
        }
    }
}
