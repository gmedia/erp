<?php

namespace App\Health\Checks;

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
                ->failed('Status migrasi tidak dapat dibaca (' . class_basename($exception) . ').');
        }

        $pending = count(array_diff(array_keys($files), $ran));

        $result = Result::make()
            ->meta([
                'pending' => $pending,
                'ran' => count($ran),
            ])
            ->shortSummary($pending === 0 ? 'Lengkap' : $pending . ' tertunda');

        if ($pending > 0) {
            return $result->warning("Ada {$pending} migrasi yang belum dijalankan.");
        }

        return $result->ok('Semua migrasi sudah dijalankan.');
    }
}
