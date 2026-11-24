<?php

use App\Support\Database\TransactionRetrier;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

uses()->group('concurrency');

beforeEach(function (): void {
    expect(DB::transactionLevel())->toBe(0);
});

/**
 * Membuat QueryException yang menyerupai galat driver MySQL/MariaDB dengan errno tertentu.
 */
function queryExceptionWithErrno(int $errno, string $message = 'simulated driver error'): QueryException
{
    $exception = new QueryException(
        'mysql',
        'update `documents` set `number` = ? where `id` = ?',
        [],
        new RuntimeException($message),
    );

    $property = new ReflectionProperty($exception, 'errorInfo');
    $property->setValue($exception, ['40001', $errno, $message]);

    return $exception;
}

/**
 * Duplicate entry pada indeks nomor dokumen (1062 yang boleh diulang).
 */
function documentNumberDuplicate(): QueryException
{
    return queryExceptionWithErrno(
        1062,
        "Duplicate entry 'PO-2026-0001' for key 'purchase_orders_number_unique'",
    );
}

it('mengenali galat konkurensi sementara', function (int $errno): void {
    expect(TransactionRetrier::isRetryable(queryExceptionWithErrno($errno)))->toBeTrue();
})->with([1213, 1205, 1020]);

it('mengenali duplicate entry hanya pada indeks nomor dokumen', function (): void {
    expect(TransactionRetrier::isRetryable(documentNumberDuplicate()))->toBeTrue()
        ->and(TransactionRetrier::isRetryable(queryExceptionWithErrno(
            1062,
            "Duplicate entry 'ABC' for key 'units_code_unique'",
        )))->toBeFalse();
});

it('tidak mengulang galat yang bukan konkurensi', function (): void {
    expect(TransactionRetrier::isRetryable(queryExceptionWithErrno(1452)))->toBeFalse()
        ->and(TransactionRetrier::isRetryable(new RuntimeException('x')))->toBeFalse();
});

it('mengulang transaksi setelah deadlock lalu berhasil', function (): void {
    $attempts = 0;

    $result = TransactionRetrier::run(function () use (&$attempts) {
        $attempts++;

        if ($attempts < 2) {
            throw queryExceptionWithErrno(1213);
        }

        return 'ok';
    });

    expect($result)->toBe('ok')
        ->and($attempts)->toBe(2);
});

it('mengulang duplicate entry nomor dokumen lalu berhasil', function (): void {
    $attempts = 0;

    $result = TransactionRetrier::run(function () use (&$attempts) {
        $attempts++;

        if ($attempts < 3) {
            throw documentNumberDuplicate();
        }

        return 'ok';
    });

    expect($result)->toBe('ok')
        ->and($attempts)->toBe(3);
});

it('menjawab 409 dengan pesan i18n bila galat konkurensi tetap terjadi', function (): void {
    $attempts = 0;

    $thrown = null;

    try {
        TransactionRetrier::run(function () use (&$attempts): void {
            $attempts++;

            throw queryExceptionWithErrno(1205);
        });
    } catch (Throwable $exception) {
        $thrown = $exception;
    }

    expect($thrown)->toBeInstanceOf(ConflictHttpException::class)
        ->and($thrown?->getStatusCode())->toBe(409)
        ->and($thrown?->getMessage())->toBe(__('concurrency.conflict'))
        ->and($attempts)->toBe(TransactionRetrier::MAX_ATTEMPTS);
});

it('tidak mengulang galat non-konkurensi', function (): void {
    $attempts = 0;
    $thrown = null;

    try {
        TransactionRetrier::run(function () use (&$attempts): void {
            $attempts++;

            throw queryExceptionWithErrno(1452);
        });
    } catch (QueryException $exception) {
        $thrown = $exception;
    }

    expect($thrown)->toBeInstanceOf(QueryException::class)
        ->and($attempts)->toBe(1);
});

it('tidak mengulang di dalam transaksi yang sudah berjalan', function (): void {
    $attempts = 0;
    $thrown = null;

    DB::beginTransaction();

    try {
        TransactionRetrier::run(function () use (&$attempts): void {
            $attempts++;

            throw queryExceptionWithErrno(1213);
        });
    } catch (QueryException $exception) {
        $thrown = $exception;
    } finally {
        DB::rollBack();
    }

    expect($thrown)->toBeInstanceOf(QueryException::class)
        ->and($attempts)->toBe(1);
});
