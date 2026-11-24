<?php

use App\Http\Controllers\Concerns\StoresItemsInTransaction;
use App\Models\Unit;
use App\Support\Database\TransactionRetrier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Issue #439, komentar "Review" di PR #449:
 * - Butir 1: retry tidak boleh menghilangkan pembaruan. Instance model yang sama dipakai
 *   ulang di percobaan kedua; percobaan kedua harus tetap menyimpan payload.
 * - Butir 2: 1062 hanya diulang untuk indeks nomor dokumen (`*_number_unique`).
 * - Butir 3: sisa galat konkurensi di jalur controller terluar dipetakan ke 409 i18n.
 * - Butir 4: efek samping (afterCommit) tidak terkirim dua kali saat percobaan diulang.
 *
 * Test ini memakai koneksi DB nyata di luar transaksi (tanpa RefreshDatabase) agar
 * TransactionRetrier benar-benar menjalankan jalur coba ulang.
 */
uses()->group('concurrency');

/**
 * Controller uji minimal untuk mengekspos StoresItemsInTransaction.
 */
function concurrencyRetryHarness(): object
{
    return new class
    {
        use StoresItemsInTransaction;

        public function update(Model $model, array $payload, ?callable $afterCommit = null): void
        {
            $this->updateWithSyncedItems(
                model: $model,
                attributes: $payload,
                items: null,
                payloadResolver: static fn (array $attributes): array => $attributes,
                syncItems: static function (): void {},
                afterCommit: $afterCommit,
            );
        }
    };
}

/**
 * Membuat QueryException yang menyerupai galat driver MariaDB dengan errno tertentu.
 */
function concurrencyRetryException(int $errno, string $message = 'simulated'): QueryException
{
    $exception = new QueryException(
        'mariadb',
        'update `units` set `name` = ? where `id` = ?',
        [],
        new RuntimeException($message),
    );

    $property = new ReflectionProperty($exception, 'errorInfo');
    $property->setValue($exception, ['40001', $errno, $message]);

    return $exception;
}

beforeEach(function (): void {
    expect(DB::transactionLevel())->toBe(0);
});

afterEach(function (): void {
    // Tidak memakai RefreshDatabase karena retry hanya aktif di luar transaksi.
    // Baris yang dibuat test dihapus sendiri.
    Unit::query()->where('name', 'like', 'unit-439-%')->delete();
});

it('tidak menghilangkan pembaruan saat instance model yang sama dipakai ulang setelah rollback', function (): void {
    $unit = Unit::create(['name' => 'unit-439-' . uniqid(), 'symbol' => 'u439']);
    $updatedName = 'unit-439-' . uniqid();
    $attempts = 0;

    TransactionRetrier::run(function () use ($unit, $updatedName, &$attempts): void {
        $attempts++;

        // Mengulang pola StoresItemsInTransaction: model di luar callback dipakai ulang.
        concurrencyRetryHarness()->update($unit, ['name' => $updatedName]);

        if ($attempts < 2) {
            throw concurrencyRetryException(1213);
        }
    });

    expect($attempts)->toBe(2)
        ->and($unit->fresh()->name)->toBe($updatedName)
        ->and($unit->name)->toBe($updatedName);
});

it('memperbarui instance pemanggil di jalur tanpa retry', function (): void {
    $unit = Unit::create(['name' => 'unit-439-' . uniqid(), 'symbol' => 'u439']);
    $updatedName = 'unit-439-' . uniqid();

    concurrencyRetryHarness()->update($unit, ['name' => $updatedName]);

    expect($unit->name)->toBe($updatedName)
        ->and($unit->fresh()->name)->toBe($updatedName);
});

it('mengulang duplicate entry hanya bila berasal dari indeks nomor dokumen', function (): void {
    $documentDuplicate = concurrencyRetryException(
        1062,
        "Duplicate entry 'PO-2026-0001' for key 'purchase_orders_number_unique'",
    );
    $otherDuplicate = concurrencyRetryException(
        1062,
        "Duplicate entry 'ABC' for key 'units_code_unique'",
    );

    expect(TransactionRetrier::isRetryable($documentDuplicate))->toBeTrue()
        ->and(TransactionRetrier::isRetryable($otherDuplicate))->toBeFalse();
});

it('memetakan sisa galat konkurensi di jalur controller menjadi 409 dengan pesan i18n', function (): void {
    $thrown = null;

    try {
        TransactionRetrier::run(function (): void {
            throw concurrencyRetryException(1205);
        });
    } catch (Throwable $exception) {
        $thrown = $exception;
    }

    expect($thrown)->toBeInstanceOf(ConflictHttpException::class)
        ->and($thrown?->getStatusCode())->toBe(409)
        ->and($thrown?->getMessage())->toBe(__('concurrency.conflict'));
});

it('menjawab 409 JSON concurrency.conflict bila galat konkurensi lolos di route API', function (): void {
    // DB::transaction biasa (tanpa TransactionRetrier): galat deadlock dilempar apa adanya
    // dan harus dipetakan handler menjadi 409 untuk permintaan JSON.
    Route::post('/api/_issue439/conflict', function (): void {
        DB::transaction(function (): void {
            throw concurrencyRetryException(1213);
        });
    });

    $response = $this->postJson('/api/_issue439/conflict');

    $response->assertStatus(409)
        ->assertJsonPath('message', __('concurrency.conflict'));
});

it('tidak menjalankan afterCommit dua kali saat percobaan diulang', function (): void {
    $unit = Unit::create(['name' => 'unit-439-' . uniqid(), 'symbol' => 'u439']);
    $updatedName = 'unit-439-' . uniqid();
    $attempts = 0;
    $dispatched = 0;

    TransactionRetrier::run(function () use ($unit, $updatedName, &$attempts, &$dispatched): void {
        $attempts++;

        DB::afterCommit(function () use (&$dispatched): void {
            $dispatched++;
        });

        $unit->update(['name' => $updatedName]);

        if ($attempts < 2) {
            throw concurrencyRetryException(1213);
        }
    });

    expect($attempts)->toBe(2)
        ->and($dispatched)->toBe(1);
});
