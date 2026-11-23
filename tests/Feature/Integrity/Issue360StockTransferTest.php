<?php

use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('stock-transfers-issue-360');

beforeEach(function () {
    actingAs(createTestUserWithPermissions([
        'stock_transfer', 'stock_transfer.create', 'stock_transfer.edit', 'stock_transfer.delete',
    ]));
});

/**
 * Gudang asal berisi $stock unit (movement goods_receipt), satu transfer draft $qty unit.
 *
 * @return array{0: StockTransfer, 1: Warehouse, 2: Warehouse, 3: Product, 4: Unit, 5: StockTransferItem}
 */
function issue360Transfer(float $stock, float $qty): array
{
    $from = Warehouse::factory()->create();
    $to = Warehouse::factory()->create();
    $product = Product::factory()->create();
    $unit = Unit::factory()->create();

    if ($stock > 0) {
        StockMovement::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $from->id,
            'movement_type' => 'goods_receipt',
            'quantity_in' => $stock,
            'quantity_out' => 0,
            'balance_after' => $stock,
            'reference_type' => GoodsReceipt::class,
            'reference_id' => random_int(900000, 999999),
            'moved_at' => now()->subDay(),
        ]);
    }

    $transfer = StockTransfer::factory()->create([
        'from_warehouse_id' => $from->id,
        'to_warehouse_id' => $to->id,
        'status' => 'draft',
    ]);
    $item = StockTransferItem::factory()->create([
        'stock_transfer_id' => $transfer->id,
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'quantity' => $qty,
        'quantity_received' => null,
    ]);

    return [$transfer, $from, $to, $product, $unit, $item];
}

/** Saldo terakhir gudang (urutan moved_at, id) dan total net per gudang untuk produk. */
function issue360Balance(int $warehouseId, int $productId): float
{
    return (float) StockMovement::query()
        ->where('warehouse_id', $warehouseId)
        ->where('product_id', $productId)
        ->orderByDesc('moved_at')->orderByDesc('id')
        ->value('balance_after');
}

function issue360Net(int $warehouseId, int $productId): float
{
    return (float) StockMovement::query()
        ->where('warehouse_id', $warehouseId)
        ->where('product_id', $productId)
        ->selectRaw('COALESCE(SUM(quantity_in) - SUM(quantity_out), 0) as net')
        ->value('net');
}

function issue360Payload(string $status, int $productId, int $unitId, ?float $received = null, bool $withItems = true): array
{
    $payload = ['status' => $status];

    if ($withItems) {
        $line = ['product_id' => $productId, 'unit_id' => $unitId, 'quantity' => 5];
        if ($received !== null) {
            $line['quantity_received'] = $received;
        }
        $payload['items'] = [$line];
    }

    return $payload;
}

describe('Issue #360 baris 1: dispatch lalu receive tidak ditolak 422 karena stok yang sudah keluar', function () {
    test('stok 5, dispatch 5 (in_transit), lalu received tanpa items → 200 dan masuk 5', function () {
        [$transfer, $from, $to, $product, $unit] = issue360Transfer(5, 5);

        putJson("/api/stock-transfers/{$transfer->id}", issue360Payload('in_transit', $product->id, $unit->id, null, true))
            ->assertOk();

        putJson("/api/stock-transfers/{$transfer->id}", ['status' => 'received'])
            ->assertOk();

        expect(issue360Net($to->id, $product->id))->toBe(5.0)
            ->and(issue360Net($from->id, $product->id))->toBe(0.0);
    });

    test('stok 5, dispatch 5, lalu received dengan items menyertakan quantity_received 5 → 200', function () {
        [$transfer, $from, $to, $product, $unit] = issue360Transfer(5, 5);

        putJson("/api/stock-transfers/{$transfer->id}", issue360Payload('in_transit', $product->id, $unit->id))
            ->assertOk();

        putJson("/api/stock-transfers/{$transfer->id}", issue360Payload('received', $product->id, $unit->id, 5.0))
            ->assertOk();

        expect(issue360Net($to->id, $product->id))->toBe(5.0);
    });
});

describe('Issue #360 baris 2: received tanpa quantity_received dianggap diterima penuh', function () {
    test('PUT status received tanpa items → masuk penuh 5 (tidak 0)', function () {
        [$transfer, $from, $to, $product, $unit] = issue360Transfer(5, 5);

        putJson("/api/stock-transfers/{$transfer->id}", issue360Payload('in_transit', $product->id, $unit->id))
            ->assertOk();

        putJson("/api/stock-transfers/{$transfer->id}", ['status' => 'received'])->assertOk();

        expect(issue360Net($to->id, $product->id))->toBe(5.0);
    });
});

describe('Issue #360 baris 3: payload UI dengan quantity_received 0 saat received tidak menghilangkan stok', function () {
    test('received dengan items quantity_received 0 eksplisit tetap menghormati 0 (stok tujuan 0, tidak hilang dari asal tanpa catatan)', function () {
        // Aturan: 0 eksplisit dihormati. Bukan "diterima 0 lalu stok hilang": sisa
        // dispatch tercatat di transfer_out, sehingga harus terlihat sebagai selisih.
        [$transfer, $from, $to, $product, $unit] = issue360Transfer(5, 5);

        putJson("/api/stock-transfers/{$transfer->id}", issue360Payload('in_transit', $product->id, $unit->id))
            ->assertOk();

        putJson("/api/stock-transfers/{$transfer->id}", issue360Payload('received', $product->id, $unit->id, 0.0))
            ->assertOk();

        expect(issue360Net($to->id, $product->id))->toBe(0.0);

        // Selisih harus dapat dilacak: barang keluar tetapi tidak diterima, dan tidak boleh
        // diam-diam hilang. Form UI tidak boleh mengirim 0 default (lihat baris 4 di UI).
        $itemReceived = (float) StockTransferItem::query()
            ->where('stock_transfer_id', $transfer->id)
            ->value('quantity_received');

        expect($itemReceived)->toBe(0.0);
    });
});

describe('Issue #360 review baris 1: item in_transit dengan quantity_received tersimpan 0 tidak menghilangkan stok', function () {
    test('in_transit dengan quantity_received null lalu received tanpa qty (form tidak mengirim) → diterima penuh 5, bukan 0', function () {
        [$transfer, $from, $to, $product, $unit, $item] = issue360Transfer(5, 5);

        putJson("/api/stock-transfers/{$transfer->id}", issue360Payload('in_transit', $product->id, $unit->id))->assertOk();

        // Setara data lama yang sudah dinormalisasi migrasi: non-received bukan lagi 0.
        expect(StockTransferItem::query()->whereKey($item->id)->value('quantity_received'))->toBeNull();

        putJson("/api/stock-transfers/{$transfer->id}", ['status' => 'received'])->assertOk();

        expect(issue360Net($to->id, $product->id))->toBe(5.0)
            ->and(issue360Net($from->id, $product->id))->toBe(0.0);
    });
});

describe('Issue #360 review baris 2: draft tidak menyimpan quantity_received sebelum dispatch', function () {
    test('respons detail mengembalikan quantity_received null (bukan string kosong yang menjadi 0 di UI)', function () {
        [$transfer, , , $product, $unit] = issue360Transfer(5, 5);

        $response = getJson("/api/stock-transfers/{$transfer->id}")->assertOk();

        expect($response->json('data.items.0.quantity_received'))->toBeNull();
    });

    test('draft qty 5 disimpan dengan quantity_received 5 dari form → nilai tersimpan null, bukan 5', function () {
        [$transfer, , , $product, $unit, $item] = issue360Transfer(5, 5);

        putJson("/api/stock-transfers/{$transfer->id}", [
            'items' => [['product_id' => $product->id, 'unit_id' => $unit->id, 'quantity' => 5, 'quantity_received' => 5]],
        ])->assertOk();

        expect(StockTransferItem::query()->where('stock_transfer_id', $transfer->id)->value('quantity_received'))->toBeNull();
    });

    test('draft qty 5 disimpan dengan qty diterima 5, dikoreksi ke 8 sebelum dispatch, lalu receive dari form → masuk 8 (tidak hilang 3)', function () {
        [$transfer, $from, $to, $product, $unit] = issue360Transfer(8, 5);

        putJson("/api/stock-transfers/{$transfer->id}", [
            'items' => [['product_id' => $product->id, 'unit_id' => $unit->id, 'quantity' => 5, 'quantity_received' => 5]],
        ])->assertOk();

        putJson("/api/stock-transfers/{$transfer->id}", [
            'status' => 'in_transit',
            'items' => [['product_id' => $product->id, 'unit_id' => $unit->id, 'quantity' => 8]],
        ])->assertOk();

        putJson("/api/stock-transfers/{$transfer->id}", [
            'status' => 'received',
            'items' => [['product_id' => $product->id, 'unit_id' => $unit->id, 'quantity' => 8, 'quantity_received' => 8]],
        ])->assertOk();

        expect(issue360Net($to->id, $product->id))->toBe(8.0);
    });
});

describe('Issue #360 review baris 3: quantity_received tidak boleh melebihi quantity', function () {
    test('received dengan quantity_received 50 dari qty 5 → 422 dan stok tujuan tidak bertambah', function () {
        [$transfer, $from, $to, $product, $unit] = issue360Transfer(5, 5);

        putJson("/api/stock-transfers/{$transfer->id}", issue360Payload('in_transit', $product->id, $unit->id))->assertOk();

        putJson("/api/stock-transfers/{$transfer->id}", issue360Payload('received', $product->id, $unit->id, 50.0))
            ->assertStatus(422);

        expect(issue360Net($to->id, $product->id))->toBe(0.0);
    });
});

describe('Issue #360 review baris 4: cancelled adalah status akhir', function () {
    test('cancelled → in_transit ditolak 422 dan tidak ada movement baru', function () {
        [$transfer, $from, $to, $product, $unit] = issue360Transfer(5, 5);

        putJson("/api/stock-transfers/{$transfer->id}", issue360Payload('in_transit', $product->id, $unit->id))->assertOk();
        putJson("/api/stock-transfers/{$transfer->id}", ['status' => 'cancelled'])->assertOk();

        $movementsBefore = StockMovement::query()->count();

        putJson("/api/stock-transfers/{$transfer->id}", issue360Payload('in_transit', $product->id, $unit->id))
            ->assertStatus(422);

        expect(StockMovement::query()->count())->toBe($movementsBefore)
            ->and(issue360Net($from->id, $product->id))->toBe(5.0);
    });

    test('cancelled → draft dan cancelled → received ditolak 422', function () {
        [$transfer, , , $product, $unit] = issue360Transfer(5, 5);

        putJson("/api/stock-transfers/{$transfer->id}", ['status' => 'cancelled'])->assertOk();

        putJson("/api/stock-transfers/{$transfer->id}", ['status' => 'draft'])->assertStatus(422);
        putJson("/api/stock-transfers/{$transfer->id}", ['status' => 'received'])->assertStatus(422);
    });

    test('edit item pada transfer cancelled ditolak 422', function () {
        [$transfer, , , $product, $unit] = issue360Transfer(5, 5);

        putJson("/api/stock-transfers/{$transfer->id}", ['status' => 'cancelled'])->assertOk();

        putJson("/api/stock-transfers/{$transfer->id}", issue360Payload('cancelled', $product->id, $unit->id))
            ->assertStatus(422);
    });
});

describe('Issue #360 batal via PUT (cancelled) pada in_transit membalik stok asal', function () {
    test('PUT cancelled pada in_transit mengembalikan saldo asal ke 5 (sama seperti DELETE)', function () {
        [$transfer, $from, $to, $product, $unit] = issue360Transfer(5, 5);

        putJson("/api/stock-transfers/{$transfer->id}", issue360Payload('in_transit', $product->id, $unit->id))
            ->assertOk();

        putJson("/api/stock-transfers/{$transfer->id}", ['status' => 'cancelled'])->assertOk();

        expect(issue360Net($from->id, $product->id))->toBe(5.0)
            ->and(issue360Balance($from->id, $product->id))->toBe(5.0);
    });

    test('DELETE in_transit mengembalikan saldo asal (baseline, sudah benar dari #356)', function () {
        [$transfer, $from, $to, $product, $unit] = issue360Transfer(5, 5);

        putJson("/api/stock-transfers/{$transfer->id}", issue360Payload('in_transit', $product->id, $unit->id))
            ->assertOk();

        deleteJson("/api/stock-transfers/{$transfer->id}")->assertNoContent();

        expect(issue360Net($from->id, $product->id))->toBe(5.0);
    });
});

describe('Issue #360 baris 4b: pembatalan transfer received membalik dua sisi', function () {
    test('PUT cancelled pada received mengembalikan asal dan mengeluarkan tujuan (net per gudang kembali ke awal)', function () {
        [$transfer, $from, $to, $product, $unit] = issue360Transfer(5, 5);

        putJson("/api/stock-transfers/{$transfer->id}", issue360Payload('in_transit', $product->id, $unit->id))->assertOk();
        putJson("/api/stock-transfers/{$transfer->id}", issue360Payload('received', $product->id, $unit->id, 5.0))->assertOk();

        putJson("/api/stock-transfers/{$transfer->id}", ['status' => 'cancelled'])->assertOk();

        expect(issue360Net($from->id, $product->id))->toBe(5.0)
            ->and(issue360Net($to->id, $product->id))->toBe(0.0);
    });
});

describe('Issue #360 baris 4c: transisi mundur ditolak', function () {
    test('received → in_transit ditolak 422', function () {
        [$transfer, $from, $to, $product, $unit] = issue360Transfer(5, 5);

        putJson("/api/stock-transfers/{$transfer->id}", issue360Payload('in_transit', $product->id, $unit->id))->assertOk();
        putJson("/api/stock-transfers/{$transfer->id}", issue360Payload('received', $product->id, $unit->id, 5.0))->assertOk();

        putJson("/api/stock-transfers/{$transfer->id}", ['status' => 'in_transit'])->assertStatus(422);
    });

    test('in_transit → draft ditolak 422', function () {
        [$transfer, $from, $to, $product, $unit] = issue360Transfer(5, 5);

        putJson("/api/stock-transfers/{$transfer->id}", issue360Payload('in_transit', $product->id, $unit->id))->assertOk();

        putJson("/api/stock-transfers/{$transfer->id}", ['status' => 'draft'])->assertStatus(422);
    });
});

describe('Issue #360 baris 5: pesan 422 memakai nama produk dan gudang, bukan id', function () {
    test('pesan stok tidak cukup menyebut nama produk dan nama gudang', function () {
        [$transfer, $from, $to, $product, $unit] = issue360Transfer(0, 5);
        $from->update(['name' => 'Gudang Utama']);
        $product->update(['name' => 'Kabel NYM 3x2.5']);

        $response = putJson("/api/stock-transfers/{$transfer->id}", issue360Payload('in_transit', $product->id, $unit->id));

        $response->assertStatus(422);
        $message = json_encode($response->json('errors'));

        expect($message)->toContain('Kabel NYM 3x2.5')
            ->and($message)->toContain('Gudang Utama')
            ->and($message)->not->toContain('produk ' . $product->id)
            ->and($message)->not->toContain('gudang ' . $from->id);
    });
});

describe('Issue #360: update berulang dan stok pas habis', function () {
    test('update berulang pada transfer in_transit tidak menggandakan movement', function () {
        [$transfer, $from, $to, $product, $unit] = issue360Transfer(5, 5);

        putJson("/api/stock-transfers/{$transfer->id}", issue360Payload('in_transit', $product->id, $unit->id))->assertOk();
        putJson("/api/stock-transfers/{$transfer->id}", issue360Payload('in_transit', $product->id, $unit->id))->assertOk();
        putJson("/api/stock-transfers/{$transfer->id}", issue360Payload('in_transit', $product->id, $unit->id))->assertOk();

        $outCount = StockMovement::query()
            ->where('reference_type', StockTransfer::class)
            ->where('reference_id', $transfer->id)
            ->where('movement_type', 'transfer_out')
            ->count();

        expect($outCount)->toBe(1)
            ->and(issue360Net($from->id, $product->id))->toBe(0.0);
    });

    test('stok pas habis (stok 5, kirim 5) lalu update ulang tidak ditolak dan tidak mengurangi lagi', function () {
        [$transfer, $from, $to, $product, $unit] = issue360Transfer(5, 5);

        putJson("/api/stock-transfers/{$transfer->id}", issue360Payload('in_transit', $product->id, $unit->id))->assertOk();
        putJson("/api/stock-transfers/{$transfer->id}", issue360Payload('in_transit', $product->id, $unit->id))->assertOk();

        expect(issue360Net($from->id, $product->id))->toBe(0.0);
    });

    test('stok kurang satu unit dari qty ditolak 422 dan tidak ada movement keluar', function () {
        [$transfer, $from, $to, $product, $unit] = issue360Transfer(4, 5);

        putJson("/api/stock-transfers/{$transfer->id}", issue360Payload('in_transit', $product->id, $unit->id))
            ->assertStatus(422);

        expect(issue360Net($from->id, $product->id))->toBe(4.0);
    });
});

describe('Issue #360: update quantity setelah dispatch tidak menggandakan movement', function () {
    test('naikkan quantity setelah in_transit: total keluar tidak melebihi qty dispatch yang sebenarnya', function () {
        [$transfer, $from, $to, $product, $unit] = issue360Transfer(10, 5);

        putJson("/api/stock-transfers/{$transfer->id}", issue360Payload('in_transit', $product->id, $unit->id))->assertOk();

        // Payload sama, qty tetap 5: tidak boleh menambah movement keluar.
        putJson("/api/stock-transfers/{$transfer->id}", issue360Payload('in_transit', $product->id, $unit->id))->assertOk();

        $outTotal = (float) StockMovement::query()
            ->where('reference_type', StockTransfer::class)
            ->where('reference_id', $transfer->id)
            ->where('movement_type', 'transfer_out')
            ->sum('quantity_out');

        expect($outTotal)->toBe(5.0)
            ->and(issue360Net($from->id, $product->id))->toBe(5.0);
    });
});

describe('Issue #360 keputusan 3: item dan qty kirim terkunci setelah dispatch', function () {
    test('mengubah quantity setelah in_transit ditolak 422 dan tidak ada movement tambahan', function () {
        [$transfer, $from, $to, $product, $unit] = issue360Transfer(10, 5);

        putJson("/api/stock-transfers/{$transfer->id}", issue360Payload('in_transit', $product->id, $unit->id))->assertOk();
        $movements = StockMovement::query()->count();

        putJson("/api/stock-transfers/{$transfer->id}", [
            'items' => [['product_id' => $product->id, 'unit_id' => $unit->id, 'quantity' => 7]],
        ])->assertStatus(422)->assertJsonValidationErrors(['items']);

        expect(StockMovement::query()->count())->toBe($movements)
            ->and(issue360Net($from->id, $product->id))->toBe(5.0);
    });

    test('mengganti produk setelah in_transit ditolak 422', function () {
        [$transfer, , , $product, $unit] = issue360Transfer(10, 5);
        $other = Product::factory()->create();

        putJson("/api/stock-transfers/{$transfer->id}", issue360Payload('in_transit', $product->id, $unit->id))->assertOk();

        putJson("/api/stock-transfers/{$transfer->id}", [
            'items' => [['product_id' => $other->id, 'unit_id' => $unit->id, 'quantity' => 5]],
        ])->assertStatus(422)->assertJsonValidationErrors(['items']);
    });

    test('quantity_received setelah in_transit tetap boleh diubah pada saat received', function () {
        [$transfer, , $to, $product, $unit] = issue360Transfer(5, 5);

        putJson("/api/stock-transfers/{$transfer->id}", issue360Payload('in_transit', $product->id, $unit->id))->assertOk();
        putJson("/api/stock-transfers/{$transfer->id}", issue360Payload('received', $product->id, $unit->id, 4.0))->assertOk();

        expect(issue360Net($to->id, $product->id))->toBe(4.0);
    });

    test('PUT received dengan quantity_received null (kosong di UI) = diterima penuh', function () {
        [$transfer, , $to, $product, $unit] = issue360Transfer(5, 5);

        putJson("/api/stock-transfers/{$transfer->id}", issue360Payload('in_transit', $product->id, $unit->id))->assertOk();
        putJson("/api/stock-transfers/{$transfer->id}", [
            'status' => 'received',
            'items' => [['product_id' => $product->id, 'unit_id' => $unit->id, 'quantity' => 5, 'quantity_received' => null]],
        ])->assertOk();

        expect(issue360Net($to->id, $product->id))->toBe(5.0);
    });

    test('PUT tanpa items setelah in_transit tetap diizinkan (hanya status)', function () {
        [$transfer, , , $product, $unit] = issue360Transfer(5, 5);

        putJson("/api/stock-transfers/{$transfer->id}", issue360Payload('in_transit', $product->id, $unit->id))->assertOk();
        putJson("/api/stock-transfers/{$transfer->id}", ['notes' => 'catatan'])->assertOk();
    });
});

describe('Issue #360: DELETE dan PUT setara untuk pembatalan posted', function () {
    test('DELETE pada received membalik dua sisi', function () {
        [$transfer, $from, $to, $product, $unit] = issue360Transfer(5, 5);

        putJson("/api/stock-transfers/{$transfer->id}", issue360Payload('in_transit', $product->id, $unit->id))->assertOk();
        putJson("/api/stock-transfers/{$transfer->id}", issue360Payload('received', $product->id, $unit->id, 5.0))->assertOk();

        deleteJson("/api/stock-transfers/{$transfer->id}")->assertNoContent();

        expect(issue360Net($from->id, $product->id))->toBe(5.0)
            ->and(issue360Net($to->id, $product->id))->toBe(0.0);
    });
});
