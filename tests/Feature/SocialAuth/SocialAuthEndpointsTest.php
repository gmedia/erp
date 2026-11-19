<?php

use App\Models\Setting;
use App\Models\User;
use App\Services\SocialAuth\SocialLoginPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class)->group('social-auth');

beforeEach(function () {
    Setting::updateOrCreate(['key' => 'password_login_enabled'], [
        'group' => 'authentication', 'key' => 'password_login_enabled',
        'value' => '1', 'type' => 'boolean',
    ]);
    Setting::updateOrCreate(['key' => 'social_login_enabled'], [
        'group' => 'authentication', 'key' => 'social_login_enabled',
        'value' => '1', 'type' => 'boolean',
    ]);
    SocialLoginPolicy::forgetCache();
});

test('social policy endpoint returns only public flags', function () {
    $response = $this->getJson('/api/social/policy');

    $response->assertOk()->assertExactJson([
        'socialLoginEnabled' => true,
        'passwordLoginEnabled' => true,
        'personalAccountsEnabled' => false,
        'providers' => ['google', 'microsoft'],
    ]);
});

test('password login is blocked when admin disables it', function () {
    Setting::set('password_login_enabled', false);

    $response = $this->postJson('/api/login', [
        'email' => 'someone@example.com',
        'password' => 'password',
    ]);

    $response->assertStatus(403);
});

test('password login works when admin enables it', function () {
    $user = User::factory()->create();

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertOk()->assertJsonStructure(['token', 'user']);
});

test('linked accounts endpoint requires authentication', function () {
    $this->getJson('/api/social/accounts')->assertStatus(401);
});

test('linked accounts endpoint returns empty list for authenticated user', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/social/accounts')
        ->assertOk()
        ->assertJson(['accounts' => []]);
});

test('set password endpoint validates confirmation', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/social/set-password', ['password' => 'newpassword'])
        ->assertStatus(422);
});

test('set password endpoint updates oauth-only user password', function () {
    $user = User::factory()->create(['password' => null]);
    Sanctum::actingAs($user);

    $this->postJson('/api/social/set-password', [
        'password' => 'newpassword',
        'password_confirmation' => 'newpassword',
    ])->assertOk();

    expect($user->fresh()->password)->not->toBeNull();
});

test('redirect endpoint rejects unsupported provider', function () {
    $this->getJson('/api/social/unknown/redirect')->assertStatus(422);
});

test('redirect endpoint is blocked when social login disabled', function () {
    Setting::set('social_login_enabled', false);

    $this->getJson('/api/social/google/redirect')->assertStatus(403);
});
