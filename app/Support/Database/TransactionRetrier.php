<?php

namespace App\Support\Database;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Throwable;

/**
 * Menjalankan transaksi dengan coba ulang terbatas untuk galat konkurensi
 * MySQL/MariaDB yang bersifat sementara: deadlock (1213), lock wait timeout (1205),
 * gagal membaca baris (1020), dan duplicate entry pada nomor dokumen (1062).
 *
 * Coba ulang hanya aman di transaksi terluar. Di dalam transaksi yang sudah berjalan,
 * callback dijalankan langsung dan galat dilempar ke pemanggil terluar yang mengulang
 * seluruh unit kerja. Efek samping di luar DB (notifikasi, berkas) harus didaftarkan
 * lewat DB::afterCommit di dalam callback agar tidak terjadi berulang saat retry.
 */
class TransactionRetrier
{
    /** Jumlah maksimal percobaan total (percobaan awal + coba ulang). */
    public const MAX_ATTEMPTS = 3;

    /** @var list<int> */
    private const RETRYABLE_ERRNOS = [1213, 1205, 1020, 1062];

    /**
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     *
     * @throws ConflictHttpException Bila galat konkurensi tetap terjadi setelah percobaan habis.
     */
    public static function run(Closure $callback, ?string $conflictMessageKey = null): mixed
    {
        if (DB::transactionLevel() > 0) {
            return DB::transaction($callback);
        }

        $attempt = 0;

        while (true) {
            $attempt++;

            try {
                return DB::transaction($callback);
            } catch (Throwable $exception) {
                if (! self::isRetryable($exception)) {
                    throw $exception;
                }

                if ($attempt >= self::MAX_ATTEMPTS) {
                    throw new ConflictHttpException(
                        __($conflictMessageKey ?? 'concurrency.conflict'),
                        $exception,
                    );
                }

                usleep(random_int(20, 120) * 1000);
            }
        }
    }

    public static function isRetryable(Throwable $exception): bool
    {
        if (! $exception instanceof QueryException) {
            return false;
        }

        $errno = $exception->errorInfo[1] ?? null;

        if (! is_int($errno) || ! in_array($errno, self::RETRYABLE_ERRNOS, true)) {
            return false;
        }

        // 1062 hanya dianggap konkurensi bila berasal dari indeks nomor dokumen
        // (`*_number_unique`). Duplicate key lain tetap galat data dan tidak diulang.
        if ($errno === 1062) {
            return (bool) preg_match('/for key \'[^\']*_number_unique\'/', $exception->getMessage());
        }

        return true;
    }
}
