<?php

namespace App\Health;

/**
 * Pertahanan kedua: menyaring pesan dan metadata sebelum dikirim ke klien atau notifikasi.
 * Menghapus IP, host:port, URL, dan kata yang menyerupai kredensial dari teks bebas.
 * Kunci metadata yang sensitif dibuang seluruhnya.
 */
final class SystemHealthSanitizer
{
    /** @var list<string> */
    private const SENSITIVE_META_KEYS = [
        'password', 'secret', 'token', 'key', 'dsn', 'endpoint', 'host', 'url', 'user', 'username', 'credential',
    ];

    public static function message(?string $message): string
    {
        if ($message === null || $message === '') {
            return '';
        }

        $clean = preg_replace('#[a-z][a-z0-9+.\-]*://[^\s`\'"]+#i', '[url disembunyikan]', $message) ?? $message;
        $clean = preg_replace('/\b\d{1,3}(?:\.\d{1,3}){3}(?::\d+)?\b/', '[alamat disembunyikan]', $clean) ?? $clean;
        $clean = preg_replace('/\b(?:password|passwd|pwd|secret|token|api[_-]?key)\s*[=:]\s*\S+/i', '[kredensial disembunyikan]', $clean) ?? $clean;

        return $clean;
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    public static function meta(array $meta): array
    {
        $clean = [];

        foreach ($meta as $key => $value) {
            if (self::isSensitiveKey((string) $key)) {
                continue;
            }

            $clean[$key] = is_string($value) ? self::message($value) : $value;
        }

        return $clean;
    }

    private static function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower($key);

        foreach (self::SENSITIVE_META_KEYS as $fragment) {
            if (str_contains($normalized, $fragment)) {
                return true;
            }
        }

        return false;
    }
}
