<?php

namespace App\Providers;

use App\Health\Checks\AppVersionCheck;
use App\Health\Checks\ContainerResourcesCheck;
use App\Health\Checks\DatabaseHealthCheck;
use App\Health\Checks\DiskUsageHealthCheck;
use App\Health\Checks\ObjectStorageCheck;
use App\Health\Checks\PendingMigrationsCheck;
use App\Health\Checks\ProductionConfigCheck;
use App\Health\Checks\RedisHealthCheck;
use Illuminate\Support\ServiceProvider;
use Spatie\Health\Checks\Checks\QueueCheck;
use Spatie\Health\Checks\Checks\ScheduleCheck;
use Spatie\Health\Facades\Health;

class SystemHealthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Health::checks([
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
