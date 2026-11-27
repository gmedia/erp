<?php

use App\Health\SystemHealthThresholds;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class)->group('system-health');

beforeEach(function () {
    Mail::fake();
    Cache::flush();
});

test('user without system_health permission gets 403 on status endpoint', function () {
    $user = createTestUserWithPermissions([]);
    Sanctum::actingAs($user, ['*']);

    $this->getJson('/api/system-health')->assertForbidden();
});

test('user without system_health permission gets 403 on run endpoint', function () {
    $user = createTestUserWithPermissions(['admin_setting']);
    Sanctum::actingAs($user, ['*']);

    $this->postJson('/api/system-health/run')->assertForbidden();
});

test('unauthenticated request is rejected on system health endpoint', function () {
    $this->getJson('/api/system-health')->assertUnauthorized();
});

test('user with system_health permission reads status with components and history', function () {
    $user = createTestUserWithPermissions(['system_health']);
    Sanctum::actingAs($user, ['*']);

    $this->postJson('/api/system-health/run')->assertOk();

    $response = $this->getJson('/api/system-health');

    $response->assertOk()
        ->assertJsonStructure([
            'checked_at',
            'overall',
            'components' => [['name', 'label', 'status', 'summary', 'message', 'meta']],
            'history',
            'thresholds',
            'version' => ['app', 'php', 'laravel'],
        ]);

    expect(collect($response->json('components'))->pluck('name')->all())
        ->toContain('database', 'redis', 'object_storage', 'queue', 'scheduler', 'disk', 'production_config', 'version', 'container_resources');
});

test('run endpoint refreshes the checked timestamp', function () {
    $user = createTestUserWithPermissions(['system_health']);
    Sanctum::actingAs($user, ['*']);

    $first = $this->postJson('/api/system-health/run')->assertOk()->json('checked_at');
    $this->travel(10)->minutes();
    $second = $this->postJson('/api/system-health/run')->assertOk()->json('checked_at');

    expect($second)->not->toBe($first);
});

test('status response never leaks env secrets or internal endpoints', function () {
    $user = createTestUserWithPermissions(['system_health']);
    Sanctum::actingAs($user, ['*']);

    $this->postJson('/api/system-health/run')->assertOk();

    $body = $this->getJson('/api/system-health')->getContent();

    $dbPassword = (string) config('database.connections.mariadb.password');

    expect($body)->not->toContain('AWS_SECRET')->not->toContain('password=');

    if ($dbPassword !== '') {
        expect($body)->not->toContain($dbPassword);
    }
});

test('thresholds are exposed from settings and can be changed', function () {
    $user = createTestUserWithPermissions(['system_health']);
    Sanctum::actingAs($user, ['*']);

    Setting::set(SystemHealthThresholds::DISK_WARNING_PERCENT, 60);

    $thresholds = $this->getJson('/api/system-health')->assertOk()->json('thresholds');

    expect($thresholds[SystemHealthThresholds::DISK_WARNING_PERCENT])->toBe(60);
});

test('health run endpoint is rate limited', function () {
    $user = createTestUserWithPermissions(['system_health']);
    Sanctum::actingAs($user, ['*']);

    for ($i = 0; $i < 10; $i++) {
        $this->postJson('/api/system-health/run');
    }

    $this->postJson('/api/system-health/run')->assertStatus(429);
});
