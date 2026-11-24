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
function queryExceptionWithErrno(int $errno): QueryException
{
    $exception = new QueryException(
        'mysql',
        'update `documents` set `number` = ? where `id` = ?',
        [],
        new RuntimeException('simulated driver error'),
    );

    $property = new ReflectionProperty($exception, 'errorInfo');
    $property->setValue($exception, ['40001', $errno, 'simulated']);

    return $exception;
}

it('mengenali galat konkurensi sementara', function (int $errno): void {
    expect(TransactionRetrier::isRetryable(queryExceptionWithErrno($errno)))->toBeTrue();
})->with([1213, 1205, 1020, 1062]);

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
            throw queryExceptionWithErrno(1062);
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
    } catch (\Throwable $exception) {
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
