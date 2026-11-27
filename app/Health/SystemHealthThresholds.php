<?php

namespace App\Health;

use App\Models\Setting;
use InvalidArgumentException;

/**
 * Ambang batas pemeriksaan kesehatan sistem. Nilai dibaca dari grup Setting
 * "system_health" sehingga bisa diubah di Pengaturan Admin.
 */
final class SystemHealthThresholds
{
    public const GROUP = 'system_health';

    public const DISK_WARNING_PERCENT = 'system_health_disk_warning_percent';

    public const DISK_FAILED_PERCENT = 'system_health_disk_failed_percent';

    public const MEMORY_WARNING_PERCENT = 'system_health_memory_warning_percent';

    public const MEMORY_FAILED_PERCENT = 'system_health_memory_failed_percent';

    public const QUEUE_FAILED_JOBS_WARNING = 'system_health_queue_failed_jobs_warning';

    public const SCHEDULER_STALE_MINUTES = 'system_health_scheduler_stale_minutes';

    public const DB_CONNECTIONS_WARNING = 'system_health_db_connections_warning';

    /** Alamat email admin IT untuk notifikasi perubahan status (kosong = tidak kirim email). */
    public const ALERT_EMAIL = 'system_health_alert_email';

    /**
     * @return array<string, array{default: int, label: string}>
     */
    public static function definitions(): array
    {
        return [
            self::DISK_WARNING_PERCENT => ['default' => 85, 'label' => 'Disk: peringatan di atas (%)'],
            self::DISK_FAILED_PERCENT => ['default' => 95, 'label' => 'Disk: gagal di atas (%)'],
            self::MEMORY_WARNING_PERCENT => ['default' => 80, 'label' => 'Memori container: peringatan di atas (%)'],
            self::MEMORY_FAILED_PERCENT => ['default' => 95, 'label' => 'Memori container: gagal di atas (%)'],
            self::QUEUE_FAILED_JOBS_WARNING => ['default' => 10, 'label' => 'Antrian: job gagal peringatan (jumlah)'],
            self::SCHEDULER_STALE_MINUTES => ['default' => 10, 'label' => 'Scheduler: tidak berjalan lebih dari (menit)'],
            self::DB_CONNECTIONS_WARNING => ['default' => 100, 'label' => 'Database: koneksi aktif peringatan (jumlah)'],
        ];
    }

    public static function alertEmail(): ?string
    {
        $value = trim((string) Setting::get(self::ALERT_EMAIL, ''));

        return $value === '' ? null : $value;
    }

    public static function get(string $key): int
    {
        $definition = self::definitions()[$key] ?? null;

        if ($definition === null) {
            throw new InvalidArgumentException("Unknown system health threshold [{$key}].");
        }

        $value = Setting::get($key);

        return is_numeric($value) ? (int) $value : $definition['default'];
    }
}
