<?php

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class)->group('auth');

test('profile update requires authentication', function () {
    $this->patchJson('/api/profile', ['name' => 'Budi Santoso'])
        ->assertUnauthorized();
});

test('authenticated user can update own name and email', function () {
    $user = User::factory()->create([
        'name' => 'Nama Lama',
        'email' => 'lama@example.com',
        'email_verified_at' => now(),
    ]);
    Sanctum::actingAs($user, ['*']);

    $this->patchJson('/api/profile', [
        'name' => 'Budi Santoso',
        'email' => 'Baru@Example.com',
    ])
        ->assertOk()
        ->assertJsonPath('user.name', 'Budi Santoso')
        ->assertJsonPath('user.email', 'baru@example.com');

    $user->refresh();
    expect($user->name)->toBe('Budi Santoso')
        ->and($user->email)->toBe('baru@example.com')
        ->and($user->email_verified_at)->toBeNull();
});

test('keeping the same email does not reset verification', function () {
    $verifiedAt = now()->subDay()->startOfSecond();
    $user = User::factory()->create([
        'email' => 'sama@example.com',
        'email_verified_at' => $verifiedAt,
    ]);
    Sanctum::actingAs($user, ['*']);

    $this->patchJson('/api/profile', [
        'name' => 'Nama Baru',
        'email' => 'sama@example.com',
    ])->assertOk();

    expect($user->refresh()->email_verified_at?->equalTo($verifiedAt))->toBeTrue();
});

test('profile update rejects an email used by another user', function () {
    User::factory()->create(['email' => 'terpakai@example.com']);
    $user = User::factory()->create(['email' => 'milikku@example.com']);
    Sanctum::actingAs($user, ['*']);

    $this->patchJson('/api/profile', [
        'name' => $user->name,
        'email' => 'terpakai@example.com',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('profile update ignores sensitive fields', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password-lama'),
        'email' => 'aman@example.com',
    ]);
    Sanctum::actingAs($user, ['*']);

    $this->patchJson('/api/profile', [
        'name' => 'Nama Baru',
        'email' => 'aman@example.com',
        'password' => 'password-baru',
        'password_confirmation' => 'password-baru',
        'role' => 'admin',
        'is_admin' => true,
        'user_id' => 999,
    ])->assertOk();

    $user->refresh();
    expect(Hash::check('password-lama', $user->password))->toBeTrue()
        ->and($user->name)->toBe('Nama Baru');
});

test('profile update keeps social accounts linked', function () {
    $user = User::factory()->create([
        'email' => 'sso@example.com',
        'password' => null,
    ]);
    SocialAccount::factory()->create([
        'user_id' => $user->id,
        'provider' => 'google',
        'provider_account_id' => 'gid-123',
        'email' => 'sso@example.com',
    ]);
    Sanctum::actingAs($user, ['*']);

    $this->patchJson('/api/profile', [
        'name' => 'Pengguna SSO',
        'email' => 'sso-baru@example.com',
    ])->assertOk();

    expect($user->socialAccounts()->where('provider_account_id', 'gid-123')->exists())->toBeTrue();
});
