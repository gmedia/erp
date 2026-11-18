<?php

use App\Actions\SocialAuth\DetectAccountTypeAction;
use App\Exceptions\SocialAuthException;
use App\Models\Setting;
use App\Services\SocialAuth\SocialLoginPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(RefreshDatabase::class)->group('social-auth');

function authSettings(array $overrides = []): void
{
    $values = array_merge([
        'social_login_enabled' => '1',
        'social_login_allow_personal' => '0',
        'social_login_allowed_domains' => '[]',
        'social_login_allowed_tenants' => '[]',
    ], $overrides);

    foreach ($values as $key => $value) {
        Setting::updateOrCreate(['key' => $key], [
            'group' => 'authentication',
            'key' => $key,
            'value' => $value,
            'type' => str_starts_with($key, 'social_login_allowed') ? 'json' : 'boolean',
        ]);
    }

    SocialLoginPolicy::forgetCache();
}

function socialiteUser(array $raw, string $email = 'user@example.com', string $id = '12345'): SocialiteUser
{
    return (new SocialiteUser)->map([
        'id' => $id,
        'nickname' => 'Example User',
        'name' => 'Example User',
        'email' => $email,
        'avatar' => null,
    ])->setRaw($raw);
}

test('google personal is rejected by default', function () {
    authSettings();

    expect(fn () => app(DetectAccountTypeAction::class)->authorize(
        'google',
        app(DetectAccountTypeAction::class)->execute('google', socialiteUser([]))
    ))->toThrow(SocialAuthException::class);
});

test('google workspace is detected from hosted domain claim', function () {
    authSettings(['social_login_allow_personal' => '0']);
    Setting::set('social_login_allowed_domains', ['example.com']);

    $detected = app(DetectAccountTypeAction::class)->execute(
        'google',
        socialiteUser(['hd' => 'example.com'])
    );

    expect($detected['account_type'])->toBe('workspace')
        ->and($detected['hosted_domain'])->toBe('example.com');

    app(DetectAccountTypeAction::class)->authorize('google', $detected);
});

test('google workspace outside allowlist is rejected', function () {
    authSettings();
    Setting::set('social_login_allowed_domains', ['allowed.example']);

    $detected = app(DetectAccountTypeAction::class)->execute(
        'google',
        socialiteUser(['hd' => 'blocked.example'])
    );

    expect(fn () => app(DetectAccountTypeAction::class)->authorize('google', $detected))
        ->toThrow(SocialAuthException::class);
});

test('empty allowlist fails closed', function () {
    authSettings(['social_login_allowed_domains' => '[]']);

    $detected = app(DetectAccountTypeAction::class)->execute(
        'google',
        socialiteUser(['hd' => 'any.example'])
    );

    expect(fn () => app(DetectAccountTypeAction::class)->authorize('google', $detected))
        ->toThrow(SocialAuthException::class);
});

test('microsoft 365 tenant is detected from tenant object', function () {
    Setting::set('social_login_allowed_tenants', ['tenant-contoh']);

    $detected = app(DetectAccountTypeAction::class)->execute(
        'microsoft',
        socialiteUser(['tenant' => ['id' => 'tenant-contoh']])
    );

    expect($detected['account_type'])->toBe('microsoft_365')
        ->and($detected['tenant_id'])->toBe('tenant-contoh');

    app(DetectAccountTypeAction::class)->authorize('microsoft', $detected);
});

test('microsoft consumer tenant is personal', function () {
    $detected = app(DetectAccountTypeAction::class)->execute(
        'microsoft',
        socialiteUser(['tenant' => null])
    );

    expect($detected['account_type'])->toBe('personal')
        ->and($detected['tenant_id'])->toBeNull();
});

test('microsoft tenant outside allowlist is rejected', function () {
    Setting::set('social_login_allowed_tenants', ['allowed-tenant']);

    $detected = app(DetectAccountTypeAction::class)->execute(
        'microsoft',
        socialiteUser(['tenant' => ['id' => 'blocked-tenant']])
    );

    expect(fn () => app(DetectAccountTypeAction::class)->authorize('microsoft', $detected))
        ->toThrow(SocialAuthException::class);
});
