<?php

use App\Actions\SocialAuth\DetectAccountTypeAction;
use App\Actions\SocialAuth\ResolveSocialUserAction;
use App\Exceptions\SocialAuthException;
use App\Models\Setting;
use App\Models\User;
use App\Services\SocialAuth\SocialLoginPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(RefreshDatabase::class)->group('social-auth');

beforeEach(function () {
    Setting::updateOrCreate(['key' => 'social_login_allowed_domains'], [
        'group' => 'authentication', 'key' => 'social_login_allowed_domains',
        'value' => json_encode(['example.com']), 'type' => 'json',
    ]);
    Setting::updateOrCreate(['key' => 'social_login_allow_personal'], [
        'group' => 'authentication', 'key' => 'social_login_allow_personal',
        'value' => '0', 'type' => 'boolean',
    ]);
    SocialLoginPolicy::forgetCache();
});

function socialUserForResolve(string $email, array $raw = []): SocialiteUser
{
    return (new SocialiteUser)->map([
        'id' => 'google-123',
        'nickname' => 'Example',
        'name' => 'Example User',
        'email' => $email,
        'avatar' => null,
    ])->setRaw(array_merge(['hd' => 'example.com', 'email_verified' => true], $raw));
}

function resolver(): ResolveSocialUserAction
{
    return new ResolveSocialUserAction(app(DetectAccountTypeAction::class));
}

test('existing user is linked to social account', function () {
    $user = User::factory()->create(['email' => 'existing@example.com']);

    $result = resolver()->execute('google', socialUserForResolve('existing@example.com'));

    expect($result['user']->id)->toBe($user->id)
        ->and($result['account_type'])->toBe('workspace')
        ->and($user->socialAccounts()->count())->toBe(1);
});

test('unregistered user is rejected when jit is disabled', function () {
    expect(fn () => resolver()->execute('google', socialUserForResolve('new@example.com')))
        ->toThrow(SocialAuthException::class);
});

test('unverified email is rejected', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    expect(fn () => resolver()->execute(
        'google',
        socialUserForResolve('existing@example.com', ['email_verified' => false])
    ))->toThrow(SocialAuthException::class);
});

test('existing social account reuses user and refreshes metadata', function () {
    $user = User::factory()->create(['email' => 'existing@example.com']);
    resolver()->execute('google', socialUserForResolve('existing@example.com'));

    $result = resolver()->execute('google', socialUserForResolve('existing@example.com'));

    expect($result['user']->id)->toBe($user->id)
        ->and($user->socialAccounts()->count())->toBe(1);
});
