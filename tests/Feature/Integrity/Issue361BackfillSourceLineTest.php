<?php

use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('stock-source-line-backfill-issue-361');

/**
 * Jalankan file migrasi backfill source_line_id (Issue #361).
 */
function issue361RunBackfill(): void
{
    $migration = require database_path('migrations/2026_11_23_100300_backfill_source_line_id_on_stock_movements.php');
    $migration->up();
}

/**
 * Hapus source_line_id dari movement untuk mensimulasikan data lama
 * (sebelum kolom ini diisi oleh posting).
 */
function issue361StripSourceLineIds(): void
{
    StockMovement::query()->update(['source_line_id' => null]);
}

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'goods_receipt', 'goods_receipt.create', 'goods_receipt.edit', 'goods_receipt.delete',
    ]);
    actingAs($user);
});

/**
 * Buat GR confirmed lewat API (jalur posting normal), lalu kembalikan ke
 * keadaan data lama.
 *
 * @param  array<int, array{product: Product, quantity: float}>  $lines
 */
function issue361ConfirmedGoodsReceipt(array $lines, ?Warehouse $warehouse = null): GoodsReceipt
{
    $warehouse ??= Warehouse::factory()->create();
    $unit = Unit::factory()->create();
    $purchaseOrder = PurchaseOrder::factory()->create([
        'warehouse_id' => $warehouse->id,
        'status' => 'confirmed',
    ]);
    $purchaseItems = [];

    foreach ($lines as $line) {
        $purchaseItems[] = $purchaseOrder->items()->create([
            'product_id' => $line['product']->id,
            'unit_id' => $unit->id,
            'quantity' => $line['quantity'],
            'unit_price' => 5000,
            'discount_percent' => 0,
            'tax_percent' => 0,
            'line_total' => $line['quantity'] * 5000,
        ]);
    }

    $goodsReceipt = GoodsReceipt::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'warehouse_id' => $warehouse->id,
        'status' => 'draft',
        'confirmed_at' => null,
        'confirmed_by' => null,
    ]);

    putJson('/api/goods-receipts/' . $goodsReceipt->id, [
        'status' => 'confirmed',
        'items' => array_map(fn (array $line, int $index) => [
            'purchase_order_item_id' => $purchaseItems[$index]->id,
            'product_id' => $line['product']->id,
            'unit_id' => $unit->id,
            'quantity_received' => $line['quantity'],
            'quantity_accepted' => $line['quantity'],
            'quantity_rejected' => 0,
            'unit_price' => 5000,
        ], $lines, array_keys($lines)),
    ])->assertOk();

    return $goodsReceipt->refresh();
}

describe('Issue #361 — backfill source_line_id pada movement lama', function () {
    test('movement lama tanpa source_line_id terisi dari baris dokumen sumber', function () {
        $product = Product::factory()->create();
        $goodsReceipt = issue361ConfirmedGoodsReceipt([['product' => $product, 'quantity' => 4]]);
        $goodsReceiptItemId = $goodsReceipt->items()->value('id');

        issue361StripSourceLineIds();
        issue361RunBackfill();

        $movement = StockMovement::query()->where('movement_type', 'goods_receipt')->sole();

        expect($movement->source_line_id)->toBe($goodsReceiptItemId);
    });

    test('backfill tidak mengubah quantity_in, quantity_out, atau balance_after', function () {
        $product = Product::factory()->create();
        issue361ConfirmedGoodsReceipt([['product' => $product, 'quantity' => 4]]);

        $before = StockMovement::query()
            ->orderBy('id')
            ->get(['id', 'quantity_in', 'quantity_out', 'balance_after'])
            ->toArray();

        issue361StripSourceLineIds();
        issue361RunBackfill();

        $after = StockMovement::query()
            ->orderBy('id')
            ->get(['id', 'quantity_in', 'quantity_out', 'balance_after'])
            ->toArray();

        expect($after)->toEqual($before);
    });

    test('produk sama di dua baris GR dipasangkan ke baris yang benar tanpa tertukar', function () {
        $product = Product::factory()->create();
        $goodsReceipt = issue361ConfirmedGoodsReceipt([
            ['product' => $product, 'quantity' => 4],
            ['product' => $product, 'quantity' => 5],
        ]);

        $itemIds = $goodsReceipt->items()->orderBy('id')->pluck('id')->all();
        $expectedByQty = [
            4 => $itemIds[0],
            5 => $itemIds[1],
        ];

        issue361StripSourceLineIds();
        issue361RunBackfill();

        StockMovement::query()
            ->where('movement_type', 'goods_receipt')
            ->get()
            ->each(function (StockMovement $movement) use ($expectedByQty): void {
                expect($movement->source_line_id)->toBe($expectedByQty[(int) $movement->quantity_in]);
            });
    });

    test('baris ambigu dilewati dan tidak ditebak (source_line_id tetap null)', function () {
        // Dua baris GR produk sama dengan qty identik: tidak ada cara membedakan
        // movement mana milik baris mana, jadi tidak boleh ditebak.
        $product = Product::factory()->create();
        issue361ConfirmedGoodsReceipt([
            ['product' => $product, 'quantity' => 4],
            ['product' => $product, 'quantity' => 4],
        ]);

        issue361StripSourceLineIds();
        issue361RunBackfill();

        $nulls = StockMovement::query()
            ->where('movement_type', 'goods_receipt')
            ->whereNull('source_line_id')
            ->count();

        expect($nulls)->toBe(2);
    });

    test('backfill dijalankan dua kali menghasilkan hasil yang sama', function () {
        $product = Product::factory()->create();
        issue361ConfirmedGoodsReceipt([['product' => $product, 'quantity' => 4]]);

        issue361StripSourceLineIds();
        issue361RunBackfill();
        $firstPass = StockMovement::query()->orderBy('id')->pluck('source_line_id', 'id')->all();

        issue361RunBackfill();
        $secondPass = StockMovement::query()->orderBy('id')->pluck('source_line_id', 'id')->all();

        expect($secondPass)->toEqual($firstPass);
    });

    test('edit GR confirmed setelah backfill tidak membentuk pembalik tambahan', function () {
        $product = Product::factory()->create();
        $goodsReceipt = issue361ConfirmedGoodsReceipt([['product' => $product, 'quantity' => 4]]);

        issue361StripSourceLineIds();
        issue361RunBackfill();

        $item = $goodsReceipt->items()->firstOrFail();
        $payload = [
            'items' => [[
                'purchase_order_item_id' => $item->purchase_order_item_id,
                'product_id' => $product->id,
                'unit_id' => $item->unit_id,
                'quantity_received' => 4,
                'quantity_accepted' => 4,
                'quantity_rejected' => 0,
                'unit_price' => 5000,
            ]],
        ];

        // Dua kali edit berurutan: tiap edit hanya boleh membalik movement aktif sekali.
        putJson('/api/goods-receipts/' . $goodsReceipt->id, $payload)->assertOk();
        putJson('/api/goods-receipts/' . $goodsReceipt->id, $payload)->assertOk();

        $reversals = StockMovement::query()
            ->where('movement_type', 'goods_receipt_reversal')
            ->where('product_id', $product->id)
            ->sum('quantity_out');
        $activeIn = StockMovement::query()
            ->where('movement_type', 'goods_receipt')
            ->where('product_id', $product->id)
            ->sum('quantity_in');

        // Stok netto harus tetap 4 (qty GR), bukan membengkak karena pembalik ganda.
        // Saldo akhir pada urutan ledger (moved_at, id), sama seperti rebuildBalances.
        $finalBalance = (float) StockMovement::query()
            ->where('product_id', $product->id)
            ->orderBy('moved_at')
            ->orderBy('id')
            ->get()
            ->last()
            ->balance_after;

        // Setiap pembalik harus membawa source_line_id dari movement asalnya (bukan null).
        // Tanpa backfill, pembalik untuk data lama tidak punya kunci dan tidak bisa dipasangkan.
        $unkeyedReversals = StockMovement::query()
            ->where('movement_type', 'goods_receipt_reversal')
            ->whereNull('source_line_id')
            ->count();

        expect((float) $activeIn - (float) $reversals)->toBe(4.0)
            ->and($finalBalance)->toBe(4.0)
            ->and($unkeyedReversals)->toBe(0)
            ->and(StockMovement::query()->where('movement_type', 'goods_receipt_reversal')->count())->toBe(2);
    });

    test('batal GR confirmed setelah backfill membalik tepat sekali dan saldo akhir 0', function () {
        $product = Product::factory()->create();
        $goodsReceipt = issue361ConfirmedGoodsReceipt([['product' => $product, 'quantity' => 4]]);

        issue361StripSourceLineIds();
        issue361RunBackfill();

        putJson('/api/goods-receipts/' . $goodsReceipt->id, ['status' => 'cancelled'])->assertOk();

        $finalBalance = (float) StockMovement::query()
            ->where('product_id', $product->id)
            ->orderByDesc('moved_at')
            ->orderByDesc('id')
            ->value('balance_after');

        $unkeyedReversals = StockMovement::query()
            ->where('movement_type', 'goods_receipt_reversal')
            ->whereNull('source_line_id')
            ->count();

        expect(StockMovement::query()->where('movement_type', 'goods_receipt_reversal')->count())->toBe(1)
            ->and($unkeyedReversals)->toBe(0)
            ->and($finalBalance)->toBe(0.0);
    });
});

describe('Issue #361 — dokumen lain (adjustment) juga dipasangkan', function () {
    test('adjustment masuk dan keluar lama memperoleh source_line_id sesuai tipe enum', function () {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();
        $unit = Unit::factory()->create();

        $adjustment = StockAdjustment::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => 'approved',
        ]);
        $inItem = $adjustment->items()->create([
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'quantity_before' => 0,
            'quantity_adjusted' => 5,
            'quantity_after' => 5,
            'unit_cost' => 1000,
            'total_cost' => 5000,
        ]);

        $movementIn = StockMovement::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'movement_type' => 'adjustment_in',
            'quantity_in' => 5,
            'quantity_out' => 0,
            'reference_type' => StockAdjustment::class,
            'reference_id' => $adjustment->id,
            'source_line_id' => null,
        ]);

        issue361RunBackfill();

        expect($movementIn->refresh()->source_line_id)->toBe($inItem->id);
    });
});

describe('Issue #361 — filter reference_type dan arah adjustment', function () {
    test('movement dengan reference_type lain tidak dipasangkan meski reference_id dan tipe sama', function () {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();
        $unit = Unit::factory()->create();

        $adjustment = StockAdjustment::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => 'approved',
        ]);
        $adjustment->items()->create([
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'quantity_before' => 0,
            'quantity_adjusted' => 5,
            'quantity_after' => 5,
            'unit_cost' => 1000,
            'total_cost' => 5000,
        ]);

        // Movement bertipe adjustment_in tetapi berasal dari dokumen lain (ID kebetulan sama).
        $foreignMovement = StockMovement::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'movement_type' => 'adjustment_in',
            'quantity_in' => 5,
            'quantity_out' => 0,
            'reference_type' => GoodsReceipt::class,
            'reference_id' => $adjustment->id,
            'source_line_id' => null,
        ]);

        issue361RunBackfill();

        expect($foreignMovement->refresh()->source_line_id)->toBeNull();
    });

    test('adjustment keluar -3 tidak dipasangkan ke baris +3 dan sebaliknya (arah dicocokkan)', function () {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();
        $unit = Unit::factory()->create();

        // Satu adjustment hanya boleh punya satu baris per produk (unique constraint),
        // jadi arah diuji lewat dua adjustment berbeda dengan qty absolut sama.
        $adjustmentPlus = StockAdjustment::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => 'approved',
        ]);
        $itemPlus = $adjustmentPlus->items()->create([
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'quantity_before' => 10,
            'quantity_adjusted' => 3,
            'quantity_after' => 13,
            'unit_cost' => 1000,
            'total_cost' => 3000,
        ]);

        $adjustmentMinus = StockAdjustment::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => 'approved',
        ]);
        $itemMinus = $adjustmentMinus->items()->create([
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'quantity_before' => 13,
            'quantity_adjusted' => -3,
            'quantity_after' => 10,
            'unit_cost' => 1000,
            'total_cost' => -3000,
        ]);

        // Movement adjustment_out milik dokumen +3 (salah arah): tidak boleh dipasangkan.
        $wrongDirection = StockMovement::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'movement_type' => 'adjustment_out',
            'quantity_in' => 0,
            'quantity_out' => 3,
            'reference_type' => StockAdjustment::class,
            'reference_id' => $adjustmentPlus->id,
            'source_line_id' => null,
        ]);
        $movementOut = StockMovement::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'movement_type' => 'adjustment_out',
            'quantity_in' => 0,
            'quantity_out' => 3,
            'reference_type' => StockAdjustment::class,
            'reference_id' => $adjustmentMinus->id,
            'source_line_id' => null,
        ]);

        issue361RunBackfill();

        expect($wrongDirection->refresh()->source_line_id)->toBeNull()
            ->and($movementOut->refresh()->source_line_id)->toBe($itemMinus->id)
            ->and($itemPlus->id)->not->toBe($movementOut->source_line_id);
    });
});

describe('Issue #361 — keamanan migrasi', function () {
    test('down() tidak menghapus kolom source_line_id dan tidak mengubah data', function () {
        $product = Product::factory()->create();
        issue361ConfirmedGoodsReceipt([['product' => $product, 'quantity' => 4]]);

        $columns = ['id', 'quantity_in', 'balance_after', 'source_line_id'];
        $before = StockMovement::query()->orderBy('id')->get($columns)->toArray();

        $migration = require database_path(
            'migrations/2026_11_23_100300_backfill_source_line_id_on_stock_movements.php',
        );
        $migration->down();

        $after = StockMovement::query()->orderBy('id')->get($columns)->toArray();

        expect(Schema::hasColumn('stock_movements', 'source_line_id'))->toBeTrue()
            ->and($after)->toEqual($before);
    });
});
