<?php

namespace App\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

/**
 * Menerjemahkan kunci bersarang dari `lang/{locale}.json` untuk pesan yang dibuat di server
 * (notifikasi, header export). `trans()` bawaan Laravel hanya membaca kunci literal di JSON,
 * sedangkan berkas ini menyimpan kelompok seperti `system_health.queue.jobs_failed` bersarang.
 *
 * Locale yang tidak tersedia jatuh ke `en`; kunci yang tetap tidak ditemukan mengembalikan
 * kunci itu sendiri (sama seperti `trans()`).
 */
final class LocaleJsonTranslator
{
    /** @var array<string, array<string, mixed>> */
    private static array $cache = [];

    /**
     * @param  array<string, scalar|null>  $params
     */
    public static function get(string $key, array $params = [], ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        $value = Arr::get(self::load($locale), $key);

        if (! is_string($value)) {
            $value = Arr::get(self::load('en'), $key);
        }

        if (! is_string($value)) {
            return $key;
        }

        foreach ($params as $name => $replacement) {
            $value = str_replace('{' . $name . '}', (string) $replacement, $value);
        }

        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    private static function load(string $locale): array
    {
        $safe = preg_match('/^[a-z]{2}(?:[-_][A-Za-z]{2,4})?$/', $locale) === 1 ? $locale : 'en';

        if (! array_key_exists($safe, self::$cache)) {
            $path = lang_path("{$safe}.json");
            $decoded = File::exists($path) ? json_decode((string) File::get($path), true) : null;

            self::$cache[$safe] = is_array($decoded) ? $decoded : [];
        }

        return self::$cache[$safe];
    }
}
