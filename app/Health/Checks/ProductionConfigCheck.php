<?php

namespace App\Health\Checks;

use App\Health\SystemHealthMessage;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

/**
 * Peringatan bila aplikasi berjalan di production dengan APP_DEBUG aktif.
 * Di environment lain status selalu OK (tidak ada yang perlu diperingatkan).
 */
class ProductionConfigCheck extends Check
{
    public function run(): Result
    {
        $environment = (string) app()->environment();
        $debug = (bool) config('app.debug');

        $baseMeta = [
            'environment' => $environment,
            'debug' => $debug,
        ];

        $result = Result::make()->shortSummary($environment);

        if ($environment === 'production' && $debug) {
            return $result
                ->meta($baseMeta + SystemHealthMessage::meta('system_health.production.debug_enabled'))
                ->failed('APP_DEBUG aktif di production. Matikan agar detail error tidak tampil ke pengguna.');
        }

        return $result
            ->meta($baseMeta + SystemHealthMessage::meta('system_health.production.ok'))
            ->ok('Konfigurasi environment sesuai.');
    }
}
