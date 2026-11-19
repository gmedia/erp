<?php

use App\Models\Setting;
use App\Services\SocialAuth\SocialLoginPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class)->group('social-auth');

beforeEach(function () {
    foreach ([
        'social_login_enabled' => ['boolean', '1'],
        'social_login_allowed_domains' => ['json', '[]'],
        'social_login_allowed_tenants' => ['json', '[]'],
        'password_login_enabled' => ['boolean', '1'],
        'social_login_allow_personal' => ['boolean', '0'],
    ] as $key => [$type, $value]) {
        Setting::create(['group' => 'authentication', 'key' => $key, 'value' => $value, 'type' => $type]);
    }
    SocialLoginPolicy::forgetCache();
});

test('admin can save allowed domains through settings endpoint', function () {
    $user = createTestUserWithPermissions(['admin_setting', 'admin_setting.edit']);
    Sanctum::actingAs($user, ['*']);

    $response = $this->putJson('/api/admin-settings', [
        'social_login_allowed_domains' => 'example.com, subsidiary.example',
    ]);

    $response->assertStatus(200);
    expect(SocialLoginPolicy::allowedDomains())->toBe(['example.com', 'subsidiary.example']);
});

test('admin can disable password login through settings endpoint', function () {
    $user = createTestUserWithPermissions(['admin_setting', 'admin_setting.edit']);
    Sanctum::actingAs($user, ['*']);

    $response = $this->putJson('/api/admin-settings', [
        'password_login_enabled' => '0',
    ]);

    $response->assertStatus(200);
    expect(SocialLoginPolicy::passwordLoginEnabled())->toBeFalse();
});
