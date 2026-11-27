<?php

use App\Health\Checks\AppVersionCheck;
use App\Health\Checks\ContainerResourcesCheck;
use App\Health\Checks\DatabaseHealthCheck;
use App\Health\Checks\DiskUsageHealthCheck;
use App\Health\Checks\PendingMigrationsCheck;
use App\Health\Checks\ProductionConfigCheck;
use App\Health\Checks\RedisHealthCheck;
use App\Health\SystemHealthThresholds;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

uses(RefreshDatabase::class)->group('system-health');

test('database check is ok when the connection answers', function () {
    $result = (new DatabaseHealthCheck)->run();

    expect((string) $result->status)->toBe('ok');
    expect($result->meta)->toHaveKey('connections');
});

test('database check fails with a sanitized message when the query throws', function () {
    DB::shouldReceive('select')->andThrow(new RuntimeException('SQLSTATE connect to 10.1.2.3:3306 user=root password=rahasia'));

    $result = (new DatabaseHealthCheck)->run();

    expect((string) $result->status)->toBe('failed');
    expect($result->getNotificationMessage())
        ->not->toContain('10.1.2.3')
        ->not->toContain('rahasia')
        ->toContain('RuntimeException');
});

test('database check warns when connections reach the configured threshold', function () {
    Setting::set(SystemHealthThresholds::DB_CONNECTIONS_WARNING, 1);

    $result = (new DatabaseHealthCheck)->run();

    expect((string) $result->status)->toBe('warning');
});

test('redis check is ok when ping succeeds', function () {
    $connection = Mockery::mock();
    $connection->shouldReceive('ping')->andReturn('PONG');
    $connection->shouldReceive('command')->with('info', ['memory'])->andReturn(['used_memory' => 2097152]);
    Redis::shouldReceive('connection')->with('default')->andReturn($connection);

    $result = (new RedisHealthCheck)->run();

    expect((string) $result->status)->toBe('ok');
    expect($result->meta['used_memory_mb'])->toBe(2.0);
});

test('redis check fails with a sanitized message when the connection throws', function () {
    Redis::shouldReceive('connection')->andThrow(new RuntimeException('Connection refused tcp://valkey.internal:6379 auth=secret'));

    $result = (new RedisHealthCheck)->run();

    expect((string) $result->status)->toBe('failed');
    expect($result->getNotificationMessage())->not->toContain('valkey.internal')->not->toContain('secret');
});

test('disk check fails when usage is above the failed threshold', function () {
    Setting::set(SystemHealthThresholds::DISK_FAILED_PERCENT, 1);

    $result = (new DiskUsageHealthCheck)->run();

    expect((string) $result->status)->toBe('failed');
    expect($result->meta)->toHaveKey('disk_used_percent');
});

test('disk check is ok when usage is below both thresholds', function () {
    Setting::set(SystemHealthThresholds::DISK_WARNING_PERCENT, 100);
    Setting::set(SystemHealthThresholds::DISK_FAILED_PERCENT, 100);

    $result = (new DiskUsageHealthCheck)->run();

    expect((string) $result->status)->toBe('ok');
});

test('container resource check always labels its scope as container', function () {
    $result = (new ContainerResourcesCheck)->run();

    expect($result->meta)->toHaveKey('scope', 'container');
    expect(in_array((string) $result->status, ['ok', 'warning', 'failed'], true))->toBeTrue();
});

test('container resource check labels the cpu load scope as kernel', function () {
    $result = (new ContainerResourcesCheck)->run();

    expect($result->meta)->toHaveKey('cpu_load_scope', 'kernel');
});

test('production config check fails when debug is on in production', function () {
    app()->detectEnvironment(fn () => 'production');
    config(['app.debug' => true]);

    $result = (new ProductionConfigCheck)->run();

    expect((string) $result->status)->toBe('failed');
});

test('production config check is ok when debug is off in production', function () {
    app()->detectEnvironment(fn () => 'production');
    config(['app.debug' => false]);

    $result = (new ProductionConfigCheck)->run();

    expect((string) $result->status)->toBe('ok');
});

test('production config check ignores debug outside production', function () {
    app()->detectEnvironment(fn () => 'local');
    config(['app.debug' => true]);

    $result = (new ProductionConfigCheck)->run();

    expect((string) $result->status)->toBe('ok');
});

test('pending migrations check is ok when every migration file has run', function () {
    $result = (new PendingMigrationsCheck)->run();

    expect((string) $result->status)->toBe('ok');
    expect($result->meta['pending'])->toBe(0);
});

test('pending migrations check warns when a migration file has not run', function () {
    $migrator = Mockery::mock(Migrator::class);
    $migrator->shouldReceive('getMigrationFiles')->andReturn([
        '2099_01_01_000000_future_a' => '/tmp/a.php',
        '2099_01_01_000001_future_b' => '/tmp/b.php',
    ]);
    $repository = Mockery::mock();
    $repository->shouldReceive('getRan')->andReturn(['2099_01_01_000000_future_a']);
    $migrator->shouldReceive('getRepository')->andReturn($repository);
    app()->instance(Migrator::class, $migrator);

    $result = (new PendingMigrationsCheck)->run();

    expect((string) $result->status)->toBe('warning');
    expect($result->meta['pending'])->toBe(1);
});

test('version check reports app php and laravel versions without failing', function () {
    config(['app.version' => '1.2.3']);

    $result = (new AppVersionCheck)->run();

    expect((string) $result->status)->toBe('ok');
    expect($result->meta)->toMatchArray([
        'app_version' => '1.2.3',
        'php_version' => PHP_VERSION,
    ]);
    expect($result->meta)->toHaveKey('laravel_version');
});
