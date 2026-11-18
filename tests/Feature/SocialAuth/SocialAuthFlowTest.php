<?php

use App\Models\Setting;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\SocialAuth\OAuthStateManager;
use App\Services\SocialAuth\SocialLoginPolicy;
use App\Services\SocialAuth\StatelessSocialiteDriver;
use App\Services\SocialAuth\StatelessSocialiteFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery as M;

uses(RefreshDatabase::class)->group('social-auth');

beforeEach(function () {
    Setting::updateOrCreate(['key' => 'social_login_enabled'], [
        'group' => 'authentication', 'key' => 'social_login_enabled',
        'value' => '1', 'type' => 'boolean',
    ]);
    Setting::updateOrCreate(['key' => 'social_login_allow_personal'], [
        'group' => 'authentication', 'key' => 'social_login_allow_personal',
        'value' => '0', 'type' => 'boolean',
    ]);
    Setting::updateOrCreate(['key' => 'social_login_allowed_domains'], [
        'group' => 'authentication', 'key' => 'social_login_allowed_domains',
        'value' => json_encode(['example.com']), 'type' => 'json',
    ]);
    Setting::updateOrCreate(['key' => 'social_login_allowed_tenants'], [
        'group' => 'authentication', 'key' => 'social_login_allowed_tenants',
        'value' => json_encode(['tenant-contoh']), 'type' => 'json',
    ]);
    SocialLoginPolicy::forgetCache();
});

afterEach(function () {
    M::close();
});

function fakeDriver(string $email, array $raw, string $id = 'ext-1'): StatelessSocialiteDriver
{
    $socialUser = (new SocialiteUser)->map([
        'id' => $id,
        'nickname' => 'Example',
        'name' => 'Example User',
        'email' => $email,
        'avatar' => null,
    ])->setRaw($raw);

    $provider = M::mock(StatelessSocialiteDriver::class);
    $provider->shouldReceive('user')->andReturn($socialUser);

    return $provider;
}

test('callback redirects to spa with ticket on successful login', function () {
    $user = User::factory()->create(['email' => 'existing@example.com']);
    $manager = app(OAuthStateManager::class);
    $state = $manager->create('google', 'login');
    $manager->rememberVerifierForExchange($state['state'], (string) $manager->verifierForNonce($state['nonce']));

    app()->instance(StatelessSocialiteFactory::class, M::mock(StatelessSocialiteFactory::class)
        ->shouldReceive('make')->andReturn(fakeDriver('existing@example.com', [
            'hd' => 'example.com', 'email_verified' => true,
        ]))->getMock());

    $response = $this->get('/api/social/google/callback?state=' . urlencode($state['state']) . '&code=fake');

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('/auth/social/callback#ticket=');
});

test('callback redirects to settings after link intent', function () {
    $user = User::factory()->create(['email' => 'existing@example.com']);
    $manager = app(OAuthStateManager::class);
    $state = $manager->create('google', 'link');
    $manager->rememberVerifierForExchange($state['state'], (string) $manager->verifierForNonce($state['nonce']));

    app()->instance(StatelessSocialiteFactory::class, M::mock(StatelessSocialiteFactory::class)
        ->shouldReceive('make')->andReturn(fakeDriver('existing@example.com', [
            'hd' => 'example.com', 'email_verified' => true,
        ]))->getMock());

    $response = $this->get('/api/social/google/callback?state=' . urlencode($state['state']) . '&code=fake');

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('/settings/social-accounts#linked=1');
});

test('callback sends two factor users to challenge page', function () {
    $user = User::factory()->create([
        'email' => 'existing@example.com',
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_confirmed_at' => now(),
    ]);
    $manager = app(OAuthStateManager::class);
    $state = $manager->create('google', 'login');
    $manager->rememberVerifierForExchange($state['state'], (string) $manager->verifierForNonce($state['nonce']));

    app()->instance(StatelessSocialiteFactory::class, M::mock(StatelessSocialiteFactory::class)
        ->shouldReceive('make')->andReturn(fakeDriver('existing@example.com', [
            'hd' => 'example.com', 'email_verified' => true,
        ]))->getMock());

    $response = $this->get('/api/social/google/callback?state=' . urlencode($state['state']) . '&code=fake');

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('/two-factor-challenge?social_ticket=');
});

test('callback redirects with error when state is invalid', function () {
    $response = $this->get('/api/social/google/callback?state=tampered&code=fake');

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('#error=invalid_state');
});

test('callback redirects with error when social login disabled', function () {
    Setting::set('social_login_enabled', false);

    $response = $this->get('/api/social/google/callback?state=x&code=fake');

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('#error=social_login_disabled');
});

test('unlink removes a linked account', function () {
    $user = User::factory()->create();
    $account = SocialAccount::factory()->for($user)->create();

    Sanctum::actingAs($user);

    $response = $this->deleteJson('/api/social/accounts/' . $account->id);

    $response->assertOk();
    expect($user->socialAccounts()->count())->toBe(0);
});

test('unlink refuses to remove last credential for oauth-only user', function () {
    $user = User::factory()->create(['password' => null]);
    $account = SocialAccount::factory()->for($user)->create();

    Sanctum::actingAs($user);

    $this->deleteJson('/api/social/accounts/' . $account->id)
        ->assertStatus(409)
        ->assertJson(['error_code' => 'last_credential']);

    expect($user->socialAccounts()->count())->toBe(1);
});

test('redirect returns provider url with state and pkce challenge', function () {
    $response = $this->getJson('/api/social/google/redirect?intent=login');

    $response->assertOk()->assertJsonStructure(['url', 'state']);
    $url = $response->json('url');

    expect($url)->toContain('accounts.google.com')
        ->and($url)->toContain('code_challenge=')
        ->and($url)->not->toContain('hd=');
});

test('microsoft redirect uses tenant config', function () {
    $response = $this->getJson('/api/social/microsoft/redirect');

    $response->assertOk();
    expect($response->json('url'))->toContain('login.microsoftonline.com');
});
