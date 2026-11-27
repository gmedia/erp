<?php

use App\Health\Checks\ContainerResourcesCheck;
use App\Health\Checks\DiskUsageHealthCheck;
use App\Health\SystemHealthThresholds;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('system-health');

/**
 * Pembacaan cgroup dibuat palsu lewat seam protected readFile(), sehingga cabang
 * pesan container bisa diuji tanpa bergantung pada /sys milik host.
 *
 * @param  array<string, string|null>  $files  path => isi (null = tidak terbaca)
 */
function containerCheckWithFiles(array $files): ContainerResourcesCheck
{
    return new class($files) extends ContainerResourcesCheck
    {
        /** @param  array<string, string|null>  $files */
        public function __construct(private array $files) {}

        protected function readFile(string $path): ?string
        {
            return $this->files[$path] ?? null;
        }
    };
}

function cgroupFiles(int $usedBytes, int $limitBytes): array
{
    return [
        '/sys/fs/cgroup/memory.current' => (string) $usedBytes,
        '/sys/fs/cgroup/memory.max' => (string) $limitBytes,
    ];
}

test('container check emits the ok message code with the percent param', function () {
    $result = containerCheckWithFiles(cgroupFiles(10, 100))->run();

    expect((string) $result->status)->toBe('ok');
    expect($result->meta['message_code'] ?? null)->toBe('system_health.container.memory_ok');
    expect($result->meta['message_params'] ?? null)->toBe(['percent' => 10]);
});

test('container check emits the warning message code with threshold params', function () {
    Setting::set(SystemHealthThresholds::MEMORY_WARNING_PERCENT, 50);
    Setting::set(SystemHealthThresholds::MEMORY_FAILED_PERCENT, 90);

    $result = containerCheckWithFiles(cgroupFiles(60, 100))->run();

    expect((string) $result->status)->toBe('warning');
    expect($result->meta['message_code'])->toBe('system_health.container.memory_warning');
    expect($result->meta['message_params'])->toBe(['percent' => 60, 'warning_at' => 50]);
});

test('container check emits the failed message code with threshold params', function () {
    Setting::set(SystemHealthThresholds::MEMORY_WARNING_PERCENT, 50);
    Setting::set(SystemHealthThresholds::MEMORY_FAILED_PERCENT, 80);

    $result = containerCheckWithFiles(cgroupFiles(95, 100))->run();

    expect((string) $result->status)->toBe('failed');
    expect($result->meta['message_code'])->toBe('system_health.container.memory_failed');
    expect($result->meta['message_params'])->toBe(['percent' => 95, 'failed_at' => 80]);
});

test('container check reports unavailable when the cgroup memory limit is unlimited', function () {
    $result = containerCheckWithFiles([
        '/sys/fs/cgroup/memory.current' => '10',
        '/sys/fs/cgroup/memory.max' => 'max',
    ])->run();

    expect((string) $result->status)->toBe('warning');
    expect($result->meta['message_code'])->toBe('system_health.container.unavailable');
    expect($result->meta['message_params'] ?? [])->toBe([]);
    expect($result->meta['cpu_load_scope'])->toBe('kernel');
});

test('container check reports unavailable when the cgroup files are missing', function () {
    $result = containerCheckWithFiles([])->run();

    expect((string) $result->status)->toBe('warning');
    expect($result->meta['message_code'])->toBe('system_health.container.unavailable');
    expect($result->meta['scope'])->toBe('container');
});

test('container check treats a zero memory limit as unavailable', function () {
    $result = containerCheckWithFiles(cgroupFiles(10, 0))->run();

    expect((string) $result->status)->toBe('warning');
    expect($result->meta['message_code'])->toBe('system_health.container.unavailable');
});

/**
 * @param  string|null  $dfUsage  nilai kolom capacity (mis. "42%") atau null bila df gagal
 */
function diskCheckWithUsage(?int $percent): DiskUsageHealthCheck
{
    return new class($percent) extends DiskUsageHealthCheck
    {
        public function __construct(private ?int $percent) {}

        protected function usedPercent(): ?int
        {
            return $this->percent;
        }
    };
}

test('disk check emits the ok message code with the percent param', function () {
    Setting::set(SystemHealthThresholds::DISK_WARNING_PERCENT, 80);
    Setting::set(SystemHealthThresholds::DISK_FAILED_PERCENT, 90);

    $result = diskCheckWithUsage(42)->run();

    expect((string) $result->status)->toBe('ok');
    expect($result->meta['message_code'])->toBe('system_health.disk.ok');
    expect($result->meta['message_params'])->toBe(['percent' => 42]);
    expect($result->meta['disk_used_percent'])->toBe(42);
});

test('disk check emits the warning message code with threshold params', function () {
    Setting::set(SystemHealthThresholds::DISK_WARNING_PERCENT, 70);
    Setting::set(SystemHealthThresholds::DISK_FAILED_PERCENT, 90);

    $result = diskCheckWithUsage(75)->run();

    expect((string) $result->status)->toBe('warning');
    expect($result->meta['message_code'])->toBe('system_health.disk.warning');
    expect($result->meta['message_params'])->toBe(['percent' => 75, 'warning_at' => 70]);
});

test('disk check emits the failed message code with threshold params', function () {
    Setting::set(SystemHealthThresholds::DISK_WARNING_PERCENT, 70);
    Setting::set(SystemHealthThresholds::DISK_FAILED_PERCENT, 85);

    $result = diskCheckWithUsage(97)->run();

    expect((string) $result->status)->toBe('failed');
    expect($result->meta['message_code'])->toBe('system_health.disk.failed');
    expect($result->meta['message_params'])->toBe(['percent' => 97, 'failed_at' => 85]);
});

test('disk check reports unavailable when df cannot be read', function () {
    $result = diskCheckWithUsage(null)->run();

    expect((string) $result->status)->toBe('warning');
    expect($result->meta['message_code'])->toBe('system_health.disk.unavailable');
    expect($result->meta)->not->toHaveKey('disk_used_percent');
});
