<?php

namespace App\Health\Checks;

use Illuminate\Support\Facades\Log;
use Spatie\Backup\BackupDestination\BackupDestination;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;
use Throwable;

/**
 * Backup terakhir harus berhasil dibuat < 26 jam lalu dengan ukuran > 0 byte.
 * Pesan error tidak menyertakan path disk atau kredensial: hanya nama kelas exception.
 */
class BackupFreshnessHealthCheck extends Check
{
    public const MAX_AGE_HOURS = 26;

    /**
     * Label diterjemahkan saat dibaca (bukan saat boot), agar mengikuti locale request.
     */
    public function getLabel(): string
    {
        return __('system_health.backup.label');
    }

    public function run(): Result
    {
        try {
            $destination = BackupDestination::create('backups', (string) config('backup.backup.name'));

            if (! $destination->isReachable()) {
                $error = $destination->connectionError();
                $name = $error ? class_basename($error) : __('system_health.backup.no_disk');

                return Result::make()->failed(__('system_health.backup.unreachable', ['error' => $name]));
            }

            $newest = $destination->newestBackup();
        } catch (Throwable $exception) {
            report($exception);
            Log::warning('Backup freshness check could not read the backup destination.');

            return Result::make()->failed(
                __('system_health.backup.unreadable', ['error' => class_basename($exception)]),
            );
        }

        if ($newest === null) {
            return Result::make()->failed(__('system_health.backup.none'));
        }

        $sizeBytes = (int) $newest->sizeInBytes();
        $ageHours = round($newest->date()->diffInMinutes(now()) / 60, 1);

        $result = Result::make()
            ->meta([
                'last_backup_at' => $newest->date()->toIso8601String(),
                'age_hours' => $ageHours,
                'size_bytes' => $sizeBytes,
            ])
            ->shortSummary(__('system_health.backup.age', ['hours' => $ageHours]));

        if ($sizeBytes <= 0) {
            return $result->failed(__('system_health.backup.empty'));
        }

        if ($ageHours >= self::MAX_AGE_HOURS) {
            return $result->failed(__('system_health.backup.stale', ['hours' => self::MAX_AGE_HOURS]));
        }

        return $result->ok(__('system_health.backup.ok'));
    }
}
