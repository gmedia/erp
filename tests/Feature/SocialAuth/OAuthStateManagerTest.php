<?php

use App\Exceptions\SocialAuthException;
use App\Services\SocialAuth\OAuthStateManager;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('social-auth');

test('oauth state is signed single use and provider bound', function () {
    $manager = app(OAuthStateManager::class);
    $state = $manager->create('google', 'login');

    $data = $manager->verify($state['state'], 'google');

    expect($data['intent'])->toBe('login')
        ->and($data['nonce'])->toBe($state['nonce']);

    expect(fn () => $manager->verify($state['state'], 'google'))
        ->toThrow(SocialAuthException::class);
});

test('oauth state from another provider is rejected', function () {
    $manager = app(OAuthStateManager::class);
    $state = $manager->create('google', 'login');

    expect(fn () => $manager->verify($state['state'], 'microsoft'))
        ->toThrow(SocialAuthException::class);
});

test('oauth state with tampered signature is rejected', function () {
    $manager = app(OAuthStateManager::class);
    $state = $manager->create('google', 'login');
    $decoded = base64_decode($state['state'], true);
    $parts = explode('|', $decoded);
    $parts[3] = str_repeat('0', 64);
    $tampered = base64_encode(implode('|', $parts));

    expect(fn () => $manager->verify($tampered, 'google'))
        ->toThrow(SocialAuthException::class);
});

test('pkce verifier can be stored and pulled by state', function () {
    $manager = app(OAuthStateManager::class);
    $state = $manager->create('google', 'login');
    $verifier = $manager->verifierForNonce($state['nonce']);

    $manager->rememberVerifierForExchange($state['state'], (string) $verifier);

    expect($manager->pullVerifierForExchange($state['state']))->toBe($verifier)
        ->and($manager->pullVerifierForExchange($state['state']))->toBeNull();
});
