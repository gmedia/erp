<?php

use App\Models\Setting;
use App\Services\SocialAuth\SocialLoginPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('social-auth');

beforeEach(function () {
    Setting::insert([
        ['group' => 'authentication', 'key' => 'social_login_enabled', 'value' => '1', 'type' => 'boolean'],
        ['group' => 'authentication', 'key' => 'password_login_enabled', 'value' => '1', 'type' => 'boolean'],
        ['group' => 'authentication', 'key' => 'social_login_allow_personal', 'value' => '0', 'type' => 'boolean'],
        ['group' => 'authentication', 'key' => 'social_login_allowed_domains', 'value' => '[]', 'type' => 'json'],
        ['group' => 'authentication', 'key' => 'social_login_allowed_tenants', 'value' => '[]', 'type' => 'json'],
    ]);
    SocialLoginPolicy::forgetCache();
});

test('policy reads admin settings and defaults to personal accounts disabled', function () {
    expect(SocialLoginPolicy::passwordLoginEnabled())->toBeTrue()
        ->and(SocialLoginPolicy::enabled())->toBeTrue()
        ->and(SocialLoginPolicy::personalAccountsEnabled())->toBeFalse()
        ->and(SocialLoginPolicy::allowedDomains())->toBe([]);
});

test('changing domain setting is reflected without deploy', function () {
    Setting::set('social_login_allowed_domains', ['example.com', 'perusahaan.contoh']);

    expect(SocialLoginPolicy::allowedDomains())->toBe(['example.com', 'perusahaan.contoh']);
});

test('public policy flags never expose domain or tenant allowlists', function () {
    Setting::set('social_login_allowed_domains', ['rahasia.example']);

    $response = $this->getJson('/api/social/policy');

    $response->assertOk()->assertJsonStructure([
        'socialLoginEnabled', 'passwordLoginEnabled', 'personalAccountsEnabled', 'providers',
    ])->assertJsonMissing(['rahasia.example']);
});

test('password login can be disabled by admin', function () {
    Setting::set('password_login_enabled', false);

    $response = $this->postJson('/api/login', [
        'email' => 'someone@example.com',
        'password' => 'password',
    ]);

    $response->assertStatus(403);
});
