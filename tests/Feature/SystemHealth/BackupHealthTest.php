<?php

use App\Health\Checks\BackupFreshnessHealthCheck;
use App\Health\SystemHealthBackupFailureNotifier;
use App\Health\SystemHealthThresholds;
use App\Models\Setting;
use App\Notifications\GenericAppNotification;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Backup\Events\BackupHasFailed;

uses(RefreshDatabase::class)->group('system-health');

beforeEach(function () {
    config(['filesystems.disks.backups' => [
        'driver' => 'local',
        'root' => storage_path('framework/testing/backups'),
        'throw' => false,
    ]]);
    Storage::fake('backups');
});

test('backup check label follows the request locale', function () {
    $check = new BackupFreshnessHealthCheck;

    app()->setLocale('id');
    expect($check->getLabel())->toBe('Backup terakhir');

    app()->setLocale('en');
    expect($check->getLabel())->toBe('Latest backup');
});

test('backup check fails when no archive exists yet', function () {
    $result = (new BackupFreshnessHealthCheck)->run();

    expect((string) $result->status)->toBe('failed');
    expect($result->getNotificationMessage())->toBe(__('system_health.backup.none'));
});

test('backup check is ok when the newest archive is fresh and non-empty', function () {
    $name = config('backup.backup.name');
    Storage::disk('backups')->put("{$name}/" . now()->format('Y-m-d-H-i-s') . '.zip', str_repeat('x', 2048));

    $result = (new BackupFreshnessHealthCheck)->run();

    expect((string) $result->status)->toBe('ok');
    expect($result->meta['size_bytes'])->toBe(2048);
});

test('backup check fails when the newest archive is older than 26 hours', function () {
    $name = config('backup.backup.name');
    $old = now()->subHours(27);
    Storage::disk('backups')->put("{$name}/" . $old->format('Y-m-d-H-i-s') . '.zip', str_repeat('x', 2048));

    $result = (new BackupFreshnessHealthCheck)->run();

    expect((string) $result->status)->toBe('failed');
    expect($result->getNotificationMessage())->toBe(__('system_health.backup.stale', ['hours' => 26]));
});

test('backup check fails when the newest archive is empty', function () {
    $name = config('backup.backup.name');
    Storage::disk('backups')->put("{$name}/" . now()->format('Y-m-d-H-i-s') . '.zip', '');

    $result = (new BackupFreshnessHealthCheck)->run();

    expect((string) $result->status)->toBe('failed');
    expect($result->getNotificationMessage())->toBe(__('system_health.backup.empty'));
});

test('backup check fails with a sanitized message when the disk is unreachable', function () {
    config(['filesystems.disks.backups' => [
        'driver' => 'sftp-missing-driver',
    ]]);

    $result = (new BackupFreshnessHealthCheck)->run();

    expect((string) $result->status)->toBe('failed');
    expect($result->getNotificationMessage())->not->toContain('password');
});

test('scheduled backup and cleanup are registered in the scheduler', function () {
    $commands = collect(app(Schedule::class)->events())
        ->map(fn ($event) => $event->command)
        ->filter()
        ->implode("\n");

    expect($commands)
        ->toContain('backup:stage-object-storage')
        ->toContain('backup:run')
        ->toContain('backup:clean');
});

test('backup source includes the staged object storage and archives go to a separate disk', function () {
    expect(config('backup.backup.source.files.include'))
        ->toBe([storage_path('app/backup-staging/object-storage')]);
    expect(config('backup.backup.destination.disks'))->toBe(['backups']);
    expect(config('filesystems.disks.backups.driver'))->toBe('local');
});

test('backup failure is notified in-app and by email once per failure event', function () {
    Mail::fake();
    Setting::set(SystemHealthThresholds::ALERT_EMAIL, 'admin@example.com');
    $user = createTestUserWithPermissions(['system_health']);

    app(SystemHealthBackupFailureNotifier::class)->notify(
        new BackupHasFailed(new RuntimeException('dump gagal')),
    );

    // Kanal mail juga menulis baris reservasi dedupe (data.channel = mail); yang in-app adalah
    // baris tanpa channel mail, dan hanya satu per penerima.
    $inApp = DatabaseNotification::query()
        ->where('notifiable_id', $user->id)
        ->where('dedupe_key', 'like', 'system_health.backup.failed:%:' . $user->id . ':database')
        ->count();

    expect($inApp)->toBe(1);
    Mail::assertQueued(GenericAppNotification::class, fn ($mail) => $mail->eventKey === 'system_health.backup.failed');
});

test('backup failure notification does not leak exception details', function () {
    Mail::fake();
    Setting::set(SystemHealthThresholds::ALERT_EMAIL, 'admin@example.com');

    app(SystemHealthBackupFailureNotifier::class)->notify(
        new BackupHasFailed(new RuntimeException('mysqldump -p rahasia tcp://10.1.2.3')),
    );

    Mail::assertQueued(GenericAppNotification::class, function ($mail) {
        return ! str_contains((string) $mail->payload['body'], 'rahasia')
            && ! str_contains((string) $mail->payload['body'], '10.1.2.3')
            && str_contains((string) $mail->payload['body'], 'RuntimeException');
    });
});
