<?php

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class)->group('device-tokens');

test('device token is hidden from json responses', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $this->postJson('/api/device-tokens', [
        'token' => 'secret-fcm-token',
        'platform' => 'android',
    ])
        ->assertCreated()
        ->assertJsonMissingPath('data.token');

    $this->getJson('/api/device-tokens')
        ->assertOk()
        ->assertJsonMissingPath('data.0.token');
});

test('device token registration is throttled', function () {
    Cache::flush();

    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    for ($i = 0; $i < 10; $i++) {
        $this->postJson('/api/device-tokens', [
            'token' => "token-{$i}",
            'platform' => 'android',
        ])->assertCreated();
    }

    $this->postJson('/api/device-tokens', [
        'token' => 'token-overflow',
        'platform' => 'android',
    ])->assertTooManyRequests();
});

test('stale device tokens are pruned', function () {
    $stale = DeviceToken::factory()->create([
        'last_seen_at' => now()->subDays(120),
    ]);
    $neverSeen = DeviceToken::factory()->create([
        'last_seen_at' => null,
        'created_at' => now()->subDays(120),
    ]);
    $fresh = DeviceToken::factory()->create([
        'last_seen_at' => now()->subDay(),
    ]);

    $this->artisan('device-tokens:prune')->assertExitCode(0);

    $this->assertDatabaseMissing('device_tokens', ['id' => $stale->id]);
    $this->assertDatabaseMissing('device_tokens', ['id' => $neverSeen->id]);
    $this->assertDatabaseHas('device_tokens', ['id' => $fresh->id]);
});
