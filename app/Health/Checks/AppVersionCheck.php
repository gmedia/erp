<?php

namespace App\Health\Checks;

use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

/**
 * Informasi versi (aplikasi, PHP, Laravel). Selalu OK: hanya menampilkan informasi.
 */
class AppVersionCheck extends Check
{
    public function run(): Result
    {
        $appVersion = (string) config('app.version', 'dev');

        return Result::make()
            ->meta([
                'app_version' => $appVersion,
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
            ])
            ->shortSummary($appVersion)
            ->ok('Versi aplikasi ' . $appVersion . '.');
    }
}
