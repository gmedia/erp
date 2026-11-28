<?php

namespace App\Actions\AuditLog;

use App\Models\Setting;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;

class PruneAuditLogAction
{
    public const DEFAULT_RETENTION_DAYS = 365;

    public const SETTING_RETENTION_DAYS = 'audit_log_retention_days';

    public static function retentionDays(): int
    {
        $days = (int) Setting::get(self::SETTING_RETENTION_DAYS, self::DEFAULT_RETENTION_DAYS);

        return $days > 0 ? $days : self::DEFAULT_RETENTION_DAYS;
    }

    /**
     * Hapus catatan audit yang lebih tua dari jendela retensi. Mengembalikan jumlah baris terhapus.
     */
    public function execute(?Carbon $now = null): int
    {
        $cutoff = ($now ?? now())->subDays(self::retentionDays());

        return Activity::query()
            ->where('created_at', '<', $cutoff)
            ->delete();
    }
}
