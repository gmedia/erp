<?php

namespace App\Providers;

use App\Health\Checks\AppVersionCheck;
use App\Health\Checks\BackupFreshnessHealthCheck;
use App\Health\Checks\ContainerResourcesCheck;
use App\Health\Checks\DatabaseHealthCheck;
use App\Health\Checks\DiskUsageHealthCheck;
use App\Health\Checks\ObjectStorageCheck;
use App\Health\Checks\PendingMigrationsCheck;
use App\Health\Checks\ProductionConfigCheck;
use App\Health\Checks\RedisHealthCheck;
use App\Health\SystemHealthBackupFailureNotifier;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Spatie\Backup\Events\BackupHasFailed;
use Spatie\Health\Checks\Checks\QueueCheck;
use Spatie\Health\Checks\Checks\ScheduleCheck;
use Spatie\Health\Facades\Health;

class SystemHealthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(
            BackupHasFailed::class,
            fn (BackupHasFailed $event) => app(SystemHealthBackupFailureNotifier::class)->notify($event),
        );

        Health::checks([
            BackupFreshnessHealthCheck::new()->name('backup')->label('Backup terakhir'),
            DatabaseHealthCheck::new()->name('database')->label('Database'),
            RedisHealthCheck::new()->name('redis')->label('Valkey / Redis'),
            ObjectStorageCheck::new()->name('object_storage')->label('Object storage'),
            PendingMigrationsCheck::new()->name('migrations')->label('Migrasi'),
            QueueCheck::new()->name('queue')->label('Antrian')->onQueue('default'),
            ScheduleCheck::new()->name('scheduler')->label('Scheduler'),
            DiskUsageHealthCheck::new()->name('disk')->label('Disk aplikasi'),
            ProductionConfigCheck::new()->name('production_config')->label('Konfigurasi produksi'),
            AppVersionCheck::new()->name('version')->label('Versi'),
            ContainerResourcesCheck::new()->name('container_resources')->label('CPU & memori container'),
        ]);
    }
}
