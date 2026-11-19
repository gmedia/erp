<?php

use App\Actions\SocialAuth\CreateSocialLoginTicketAction;
use App\Models\SocialLoginTicket;
use App\Models\User;
use App\Services\SocialAuth\OAuthStateManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Mockery as M;

uses(RefreshDatabase::class)->group('social-auth');

afterEach(function () {
    M::close();
});

function twoFactorUser(): User
{
    return User::factory()->create([
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_confirmed_at' => now(),
    ]);
}

test('two factor verify rejects invalid ticket', function () {
    $response = $this->postJson('/api/social/two-factor/verify', [
        'social_ticket' => 'bogus',
        'code' => '123456',
    ]);

    $response->assertStatus(401)->assertJson(['error_code' => 'ticket_invalid']);
});

test('two factor verify rejects wrong code and consumes ticket', function () {
    $user = twoFactorUser();
    $ticket = app(CreateSocialLoginTicketAction::class)->execute(
        $user, request(), 'nonce-2fa', 'google', 'workspace'
    );

    app()->instance(TwoFactorAuthenticationProvider::class, M::mock(TwoFactorAuthenticationProvider::class)
        ->shouldReceive('verify')->andReturn(false)->getMock());

    $response = $this->postJson('/api/social/two-factor/verify', [
        'social_ticket' => $ticket,
        'code' => '000000',
    ]);

    $response->assertStatus(422)->assertJson(['error_code' => 'invalid_two_factor_code']);
    expect(SocialLoginTicket::query()->whereNull('consumed_at')->count())->toBe(0);
});

test('two factor verify issues fresh ticket on valid code', function () {
    $user = twoFactorUser();
    $ticket = app(CreateSocialLoginTicketAction::class)->execute(
        $user, request(), 'nonce-2fa-ok', 'google', 'workspace'
    );

    app()->instance(TwoFactorAuthenticationProvider::class, M::mock(TwoFactorAuthenticationProvider::class)
        ->shouldReceive('verify')->andReturn(true)->getMock());

    $response = $this->postJson('/api/social/two-factor/verify', [
        'social_ticket' => $ticket,
        'code' => '123456',
    ]);

    $response->assertOk()->assertJsonStructure(['ticket']);
    expect(SocialLoginTicket::query()->whereNull('consumed_at')->count())->toBe(1);
});

test('oauth state manager exposes verifier for nonce', function () {
    $manager = app(OAuthStateManager::class);
    $state = $manager->create('google', 'login');

    expect($manager->verifierForNonce($state['nonce']))
        ->toBeString()
        ->and($manager->verifierForNonce('unknown'))->toBeNull();
});
