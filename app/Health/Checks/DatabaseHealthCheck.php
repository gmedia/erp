<?php

namespace App\Health\Checks;

use App\Health\SystemHealthMessage;
use App\Health\SystemHealthThresholds;
use Illuminate\Support\Facades\DB;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;
use Throwable;

/**
 * Koneksi database, ukuran database, dan jumlah koneksi aktif. Pesan error tidak
 * menyertakan host, user, atau password: hanya nama kelas exception.
 */
class DatabaseHealthCheck extends Check
{
    public function run(): Result
    {
        try {
            DB::select('select 1');
            $sizeMb = $this->databaseSizeMb();
            $connections = $this->activeConnections();
        } catch (Throwable $exception) {
            report($exception);

            return Result::make()
                ->failed('Database tidak dapat diakses (' . class_basename($exception) . ').')
                ->meta(SystemHealthMessage::meta('system_health.database.unreachable', [
                    'exception' => class_basename($exception),
                ]));
        }

        $warningAt = SystemHealthThresholds::get(SystemHealthThresholds::DB_CONNECTIONS_WARNING);

        $baseMeta = [
            'size_mb' => $sizeMb,
            'connections' => $connections,
            'connections_warning_at' => $warningAt,
        ];

        $result = Result::make()
            ->shortSummary($connections . ' koneksi');

        if ($connections !== null && $connections >= $warningAt) {
            return $result
                ->meta($baseMeta + SystemHealthMessage::meta('system_health.database.connections_high', [
                    'connections' => $connections,
                    'warning_at' => $warningAt,
                ]))
                ->warning("Jumlah koneksi database {$connections} mendekati batas peringatan {$warningAt}.");
        }

        return $result
            ->meta($baseMeta + SystemHealthMessage::meta('system_health.database.ok'))
            ->ok('Database dapat diakses.');
    }

    private function databaseSizeMb(): ?float
    {
        $driver = DB::getDriverName();

        if ($driver !== 'mysql' && $driver !== 'mariadb') {
            return null;
        }

        $schema = DB::getDatabaseName();

        $size = DB::selectOne(
            'select coalesce(sum(data_length + index_length), 0) as bytes
             from information_schema.tables where table_schema = ?',
            [$schema],
        );

        return round(((int) ($size->bytes ?? 0)) / 1048576, 2);
    }

    private function activeConnections(): ?int
    {
        $driver = DB::getDriverName();

        if ($driver !== 'mysql' && $driver !== 'mariadb') {
            return null;
        }

        $row = DB::selectOne("show global status like 'Threads_connected'");

        return $row === null ? null : (int) ($row->Value ?? 0);
    }
}
