<?php

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class)->group('device-tokens');

test('user can register a device token', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $this->postJson('/api/device-tokens', [
        'token' => 'fcm-token-1',
        'platform' => 'android',
        'device_name' => 'Pixel',
    ])
        ->assertCreated()
        ->assertJsonPath('data.platform', 'android');

    $this->assertDatabaseHas('device_tokens', [
        'user_id' => $user->id,
        'token' => 'fcm-token-1',
        'platform' => 'android',
    ]);
});

test('registering an existing token reassigns it to the current user', function () {
    $first = User::factory()->create();
    $second = User::factory()->create();
    $token = DeviceToken::factory()->create(['user_id' => $first->id, 'token' => 'shared-token']);

    Sanctum::actingAs($second, ['*']);

    $this->postJson('/api/device-tokens', [
        'token' => 'shared-token',
        'platform' => 'android',
    ])->assertCreated();

    expect($token->fresh()->user_id)->toBe($second->id);
});

test('user only lists their own device tokens', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    DeviceToken::factory()->create(['user_id' => $user->id, 'token' => 'mine']);
    DeviceToken::factory()->create(['user_id' => $other->id, 'token' => 'theirs']);

    Sanctum::actingAs($user, ['*']);

    $this->getJson('/api/device-tokens')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.token', 'mine');
});

test('user can delete their own device token', function () {
    $user = User::factory()->create();
    $token = DeviceToken::factory()->create(['user_id' => $user->id]);

    Sanctum::actingAs($user, ['*']);

    $this->deleteJson("/api/device-tokens/{$token->id}")->assertNoContent();

    $this->assertDatabaseMissing('device_tokens', ['id' => $token->id]);
});

test('user cannot delete another users device token', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $token = DeviceToken::factory()->create(['user_id' => $other->id]);

    Sanctum::actingAs($user, ['*']);

    $this->deleteJson("/api/device-tokens/{$token->id}")->assertForbidden();

    $this->assertDatabaseHas('device_tokens', ['id' => $token->id]);
});

test('logout revokes all device tokens', function () {
    $user = User::factory()->create();
    DeviceToken::factory()->count(2)->create(['user_id' => $user->id]);

    Sanctum::actingAs($user, ['*']);

    $this->postJson('/api/logout')->assertOk();

    $this->assertDatabaseCount('device_tokens', 0);
});
