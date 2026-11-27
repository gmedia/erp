<?php

namespace App\Actions\SecurityLogs;

use App\Models\SecurityLog;
use App\Models\Setting;
use Illuminate\Support\Carbon;

class PruneSecurityLogsAction
{
    public const DEFAULT_RETENTION_DAYS = 365;

    public const SETTING_RETENTION_DAYS = 'security_log_retention_days';

    public static function retentionDays(): int
    {
        $days = (int) Setting::get(self::SETTING_RETENTION_DAYS, self::DEFAULT_RETENTION_DAYS);

        return $days > 0 ? $days : self::DEFAULT_RETENTION_DAYS;
    }

    /**
     * Delete logs older than the retention window. Returns the number of deleted rows.
     */
    public function execute(?Carbon $now = null): int
    {
        $cutoff = ($now ?? now())->subDays(self::retentionDays());

        return SecurityLog::query()
            ->where('created_at', '<', $cutoff)
            ->delete();
    }
}
