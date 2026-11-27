<?php

namespace App\Health;

/**
 * Kode pesan stabil untuk hasil cek kesehatan.
 *
 * Setiap pesan dikirim sebagai kode + parameter (`system_health.<cek>.<alasan>`), lalu
 * diterjemahkan di frontend lewat `t()`. Pesan teks asli dari paket spatie tidak pernah
 * ditampilkan mentah; paket dipetakan ke kode di sini.
 */
final class SystemHealthMessage
{
    /**
     * Teks pesan paket spatie yang dipetakan ke kode. Kunci: awalan teks paket.
     *
     * @var array<string, string>
     */
    private const PACKAGE_MESSAGE_CODES = [
        'Queue jobs running failed' => 'system_health.queue.jobs_failed',
        'The schedule did not run yet.' => 'system_health.schedule.not_run_yet',
        'The last run of the schedule' => 'system_health.schedule.stale',
    ];

    /**
     * Kode + parameter yang ditaruh di meta hasil cek custom. Action membacanya sebagai
     * `message_code` / `message_params` lalu tidak meneruskannya ke meta klien.
     *
     * @param  array<string, scalar|null>  $params
     * @return array{message_code: string, message_params: array<string, scalar|null>}
     */
    public static function meta(string $code, array $params = []): array
    {
        return ['message_code' => $code, 'message_params' => $params];
    }

    /**
     * Memetakan pesan paket (teks Inggris) ke kode + parameter. Pesan kosong atau tidak
     * dikenal menghasilkan `code = null`, sehingga klien menampilkan teks yang sudah disanitasi.
     *
     * @param  array<array-key, mixed>  $meta
     * @return array{code: string|null, params: array<string, string>}
     */
    public static function fromPackage(string $message, array $meta = []): array
    {
        if ($message === '') {
            return ['code' => null, 'params' => []];
        }

        // Cek custom sudah mengirim kode stabil langsung; jangan diterjemahkan ulang.
        if (str_starts_with($message, 'system_health.')) {
            return ['code' => $message, 'params' => []];
        }

        $code = self::codeFor($message);

        if ($code === 'system_health.queue.jobs_failed') {
            // QueueCheck menyimpan satu entri per antrian yang gagal di meta (list berindeks).
            // Tanpa entri (riwayat lama tanpa meta) jangan tampil "gagal pada 0 antrian".
            $count = count($meta);

            return $count === 0
                ? ['code' => 'system_health.queue.failed_unknown', 'params' => []]
                : ['code' => $code, 'params' => ['count' => (string) $count]];
        }

        return ['code' => $code, 'params' => []];
    }

    private static function codeFor(string $message): ?string
    {
        foreach (self::PACKAGE_MESSAGE_CODES as $prefix => $code) {
            if (str_starts_with($message, $prefix)) {
                return $code;
            }
        }

        return null;
    }
}
