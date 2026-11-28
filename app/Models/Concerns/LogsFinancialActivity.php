<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Audit perubahan data (activitylog) untuk model keuangan & pengaturan.
 *
 * - Hanya kolom yang berubah yang dicatat (old → new).
 * - Field sensitif (password, token, secret) selalu disaring.
 * - Causer = user yang login (diisi otomatis oleh paket).
 */
trait LogsFinancialActivity
{
    use LogsActivity;

    /**
     * @return list<string>
     */
    public static function defaultAuditExcludedAttributes(): array
    {
        return [
            'password',
            'remember_token',
            'token',
            'secret',
            'api_key',
            'access_token',
            'refresh_token',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->logExcept($this->auditExcludedAttributes())
            ->useLogName('finance');
    }

    /**
     * Kolom tambahan yang disaring untuk model ini. Override bila perlu.
     *
     * @return list<string>
     */
    protected function auditExcludedAttributes(): array
    {
        return self::defaultAuditExcludedAttributes();
    }
}
