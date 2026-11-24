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

test('authenticated user can update own name', function () {
    $user = User::factory()->create([
        'name' => 'Nama Lama',
        'email' => 'lama@example.com',
        'email_verified_at' => now(),
    ]);
    Sanctum::actingAs($user, ['*']);

    $this->patchJson('/api/profile', ['name' => 'Budi Santoso'])
        ->assertOk()
        ->assertJsonPath('message', 'Profile updated successfully.')
        ->assertJsonPath('user.name', 'Budi Santoso')
        ->assertJsonPath('user.email', 'lama@example.com');

    $user->refresh();
    expect($user->name)->toBe('Budi Santoso')
        ->and($user->email)->toBe('lama@example.com');
});

test('profile update requires a name', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $this->patchJson('/api/profile', ['name' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name'])
        ->assertJsonPath('errors.name.0', 'The name field is required.');
});

test('profile update ignores email and keeps verification', function () {
    $verifiedAt = now()->subDay()->startOfSecond();
    $user = User::factory()->create([
        'email' => 'milikku@example.com',
        'email_verified_at' => $verifiedAt,
    ]);
    Sanctum::actingAs($user, ['*']);

    $this->patchJson('/api/profile', [
        'name' => 'Nama Baru',
        'email' => 'orang-lain@example.com',
    ])
        ->assertOk()
        ->assertJsonPath('user.email', 'milikku@example.com');

    $user->refresh();
    expect($user->name)->toBe('Nama Baru')
        ->and($user->email)->toBe('milikku@example.com')
        ->and($user->email_verified_at?->equalTo($verifiedAt))->toBeTrue();
});

test('profile update does not accept an email already used by another user', function () {
    User::factory()->create(['email' => 'terpakai@example.com']);
    $user = User::factory()->create(['email' => 'milikku@example.com']);
    Sanctum::actingAs($user, ['*']);

    $this->patchJson('/api/profile', [
        'name' => $user->name,
        'email' => 'terpakai@example.com',
    ])->assertOk();

    expect($user->refresh()->email)->toBe('milikku@example.com');
});

test('uppercase own email does not change email or verification', function () {
    $verifiedAt = now()->subDay()->startOfSecond();
    $user = User::factory()->create([
        'email' => 'sama@example.com',
        'email_verified_at' => $verifiedAt,
    ]);
    Sanctum::actingAs($user, ['*']);

    $this->patchJson('/api/profile', [
        'name' => 'Nama Baru',
        'email' => 'SAMA@Example.com',
    ])
        ->assertOk()
        ->assertJsonPath('user.email', 'sama@example.com');

    $user->refresh();
    expect($user->email)->toBe('sama@example.com')
        ->and($user->email_verified_at?->equalTo($verifiedAt))->toBeTrue();
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

    expect($user->refresh()->email)->toBe('sso@example.com')
        ->and($user->socialAccounts()->where('provider_account_id', 'gid-123')->exists())->toBeTrue();
});
