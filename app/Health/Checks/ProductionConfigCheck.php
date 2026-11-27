<?php

namespace App\Health\Checks;

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

        $result = Result::make()
            ->meta([
                'environment' => $environment,
                'debug' => $debug,
            ])
            ->shortSummary($environment);

        if ($environment === 'production' && $debug) {
            return $result->failed('APP_DEBUG aktif di production. Matikan agar detail error tidak tampil ke pengguna.');
        }

        return $result->ok('Konfigurasi environment sesuai.');
    }
}
