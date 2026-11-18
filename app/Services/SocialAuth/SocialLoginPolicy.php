<?php

namespace App\Services\SocialAuth;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Single source of truth for runtime social-login policy.
 *
 * Values are stored in the `settings` table so administrators can change
 * them from the UI. Defaults come from config/social_login.php and contain
 * no organization-specific data.
 */
class SocialLoginPolicy
{
    public const CACHE_KEY = 'social_login_policy';

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(10), function (): array {
            $defaults = config('social_login.defaults', []);

            $defaults = config('social_login.defaults', []);
            $flag = fn (string $key, bool $fallback): bool => $defaults[$key] ?? $fallback;

            return [
                'enabled' => self::bool('social_login_enabled', $flag('enabled', true)),
                'jit_provisioning' => self::bool('social_login_jit_provisioning', $flag('jit_provisioning', false)),
                'auto_link_by_email' => self::bool(
                    'social_login_auto_link_by_email',
                    $flag('auto_link_by_email', false)
                ),
                'allow_personal' => self::bool('social_login_allow_personal', $flag('allow_personal', false)),
                'password_login_enabled' => self::bool('password_login_enabled', $flag('password_login_enabled', true)),
                'allowed_domains' => self::list('social_login_allowed_domains', $defaults['allowed_domains'] ?? []),
                'allowed_tenants' => self::list('social_login_allowed_tenants', $defaults['allowed_tenants'] ?? []),
            ];
        });
    }

    public static function enabled(): bool
    {
        return (bool) static::all()['enabled'];
    }

    public static function passwordLoginEnabled(): bool
    {
        return (bool) static::all()['password_login_enabled'];
    }

    public static function personalAccountsEnabled(): bool
    {
        return (bool) static::all()['allow_personal'];
    }

    public static function autoLinkByEmailEnabled(): bool
    {
        return (bool) static::all()['auto_link_by_email'];
    }

    public static function jitProvisioningEnabled(): bool
    {
        return (bool) static::all()['jit_provisioning'];
    }

    /**
     * @return list<string>
     */
    public static function allowedDomains(): array
    {
        /** @var list<string> $domains */
        $domains = static::all()['allowed_domains'];

        return $domains;
    }

    /**
     * @return list<string>
     */
    public static function allowedTenants(): array
    {
        /** @var list<string> $tenants */
        $tenants = static::all()['allowed_tenants'];

        return $tenants;
    }

    /**
     * Flags safe to expose to the SPA. Never include domain/tenant allowlists.
     *
     * @return array<string, mixed>
     */
    public static function publicFlags(): array
    {
        $policy = static::all();

        return [
            'socialLoginEnabled' => $policy['enabled'],
            'passwordLoginEnabled' => $policy['password_login_enabled'],
            'personalAccountsEnabled' => $policy['allow_personal'],
            'providers' => ['google', 'microsoft'],
        ];
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private static function bool(string $key, bool $default): bool
    {
        $value = Setting::get($key, null);

        return $value === null ? $default : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param  list<string>  $default
     * @return list<string>
     */
    private static function list(string $key, array $default): array
    {
        $value = Setting::get($key, null);

        if ($value === null) {
            return $default;
        }

        if (is_array($value)) {
            /** @var list<string> $items */
            $items = array_values(array_filter(array_map('strval', $value), fn (string $v): bool => $v !== ''));

            return $items;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        /** @var list<string> $items */
        $items = array_values(array_filter(array_map('trim', explode(',', $value)), fn (string $v): bool => $v !== ''));

        return $items;
    }
}
