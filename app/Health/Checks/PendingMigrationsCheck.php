<?php

namespace App\Health\Checks;

use App\Health\SystemHealthMessage;
use Illuminate\Database\Migrations\Migrator;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;
use Throwable;

/**
 * Migrasi yang belum dijalankan. Peringatan bila ada migrasi tertunda, gagal bila
 * tabel migrasi tidak bisa dibaca (database tidak sehat).
 */
class PendingMigrationsCheck extends Check
{
    public function run(): Result
    {
        try {
            $migrator = app(Migrator::class);
            $files = $migrator->getMigrationFiles(database_path('migrations'));
            $ran = $migrator->getRepository()->getRan();
        } catch (Throwable $exception) {
            report($exception);

            return Result::make()
                ->meta(SystemHealthMessage::meta('system_health.migrations.unreadable', [
                    'exception' => class_basename($exception),
                ]))
                ->failed('Status migrasi tidak dapat dibaca (' . class_basename($exception) . ').');
        }

        $pending = count(array_diff(array_keys($files), $ran));

        $baseMeta = [
            'pending' => $pending,
            'ran' => count($ran),
        ];

        $result = Result::make()
            ->shortSummary($pending === 0 ? 'Lengkap' : $pending . ' tertunda');

        if ($pending > 0) {
            return $result
                ->meta($baseMeta + SystemHealthMessage::meta('system_health.migrations.pending', ['pending' => $pending]))
                ->warning("Ada {$pending} migrasi yang belum dijalankan.");
        }

        return $result
            ->meta($baseMeta + SystemHealthMessage::meta('system_health.migrations.complete'))
            ->ok('Semua migrasi sudah dijalankan.');
    }
}
