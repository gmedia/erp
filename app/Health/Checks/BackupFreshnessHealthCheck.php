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

    public function run(): Result
    {
        try {
            $destination = BackupDestination::create('backups', (string) config('backup.backup.name'));

            if (! $destination->isReachable()) {
                $error = $destination->connectionError();

                return Result::make()->failed(
                    'Tujuan backup tidak dapat dijangkau (' . ($error ? class_basename($error) : 'disk tidak ada') . ').',
                );
            }

            $newest = $destination->newestBackup();
        } catch (Throwable $exception) {
            report($exception);
            Log::warning('Backup freshness check could not read the backup destination.');

            return Result::make()->failed('Tujuan backup tidak dapat dibaca (' . class_basename($exception) . ').');
        }

        if ($newest === null) {
            return Result::make()->failed('Belum ada arsip backup.');
        }

        $sizeBytes = (int) $newest->sizeInBytes();
        $ageHours = round($newest->date()->diffInMinutes(now()) / 60, 1);

        $result = Result::make()
            ->meta([
                'last_backup_at' => $newest->date()->toIso8601String(),
                'age_hours' => $ageHours,
                'size_bytes' => $sizeBytes,
            ])
            ->shortSummary($ageHours . ' jam lalu');

        if ($sizeBytes <= 0) {
            return $result->failed('Arsip backup terakhir berukuran 0 byte.');
        }

        if ($ageHours >= self::MAX_AGE_HOURS) {
            return $result->failed('Backup terakhir lebih dari ' . self::MAX_AGE_HOURS . ' jam lalu.');
        }

        return $result->ok('Backup terakhir berhasil dibuat.');
    }
}
