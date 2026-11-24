<?php

use App\Models\FiscalYear;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\SupplierReturn;
use App\Models\SupplierReturnItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockPostingService;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\deleteJson;

uses(RefreshDatabase::class)->group('stock-posting-issue-372');

/**
 * Posting GR confirmed bertanggal $receiptDate dengan satu baris $quantity.
 */
function issue372GoodsReceipt(
    Warehouse $warehouse,
    Product $product,
    float $quantity,
    CarbonInterface $receiptDate,
): GoodsReceipt {
    /** @var GoodsReceipt $receipt */
    $receipt = GoodsReceipt::factory()->create([
        'warehouse_id' => $warehouse->id,
        'receipt_date' => $receiptDate->toDateString(),
        'status' => 'confirmed',
        'confirmed_by' => User::factory()->create()->id,
        'confirmed_at' => now(),
    ]);

    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $receipt->id,
        'product_id' => $product->id,
        'quantity_received' => $quantity,
        'quantity_accepted' => $quantity,
        'quantity_rejected' => 0,
    ]);

    /** @var GoodsReceipt $receipt */
    $receipt = GoodsReceipt::query()->findOrFail($receipt->id);
    app(StockPostingService::class)->postGoodsReceipt($receipt);

    return $receipt;
}

/**
 * Edit item GR: pembalik bertanggal movement asli, lalu item baru diposting ulang
 * (sama seperti alur edit di GoodsReceiptController).
 */
function issue372EditGoodsReceipt(GoodsReceipt $receipt, Product $product, float $quantity): void
{
    $service = app(StockPostingService::class);

    $repost = function () use ($receipt, $product, $quantity, $service): void {
        $receipt->items()->delete();
        GoodsReceiptItem::factory()->create([
            'goods_receipt_id' => $receipt->id,
            'product_id' => $product->id,
            'quantity_received' => $quantity,
            'quantity_accepted' => $quantity,
            'quantity_rejected' => 0,
        ]);

        $service->postGoodsReceipt($receipt->refresh());
    };

    $service->editDocument(GoodsReceipt::class, $receipt->id, 'goods_receipt', $repost);
}

/**
 * Pengeluaran stok lewat penyesuaian approved (qty negatif) bertanggal $date.
 */
function issue372Outflow(Warehouse $warehouse, Product $product, float $quantity, CarbonInterface $date): void
{
    /** @var StockAdjustment $adjustment */
    /** @var StockAdjustment $adjustment */
    $adjustment = StockAdjustment::factory()->create([
        'warehouse_id' => $warehouse->id,
        'adjustment_date' => $date->toDateString(),
        'status' => 'approved',
        'inventory_stocktake_id' => null,
        'journal_entry_id' => null,
        'approved_by' => User::factory()->create()->id,
        'approved_at' => now(),
    ]);

    StockAdjustmentItem::factory()->create([
        'stock_adjustment_id' => $adjustment->id,
        'product_id' => $product->id,
        'quantity_adjusted' => -$quantity,
    ]);

    /** @var StockAdjustment $adjustment */
    $adjustment = StockAdjustment::query()->findOrFail($adjustment->id);
    app(StockPostingService::class)->postStockAdjustment($adjustment);
}

/**
 * Saldo berjalan pada akhir hari $date (movement terakhir dengan moved_at <= $date).
 */
function issue372BalanceOn(Product $product, Warehouse $warehouse, CarbonInterface $date): float
{
    return (float) StockMovement::query()
        ->where('product_id', $product->id)
        ->where('warehouse_id', $warehouse->id)
        ->where('moved_at', '<=', $date->copy()->endOfDay())
        ->orderByDesc('moved_at')
        ->orderByDesc('id')
        ->value('balance_after');
}

/**
 * Penyesuaian approved dengan satu baris $quantity bertanggal $date.
 */
function issue372Adjustment(
    Warehouse $warehouse,
    Product $product,
    float $quantity,
    CarbonInterface $date,
): StockAdjustment {
    /** @var StockAdjustment $adjustment */
    $adjustment = StockAdjustment::factory()->create([
        'warehouse_id' => $warehouse->id,
        'adjustment_date' => $date->toDateString(),
        'status' => 'approved',
        'inventory_stocktake_id' => null,
        'journal_entry_id' => null,
        'approved_by' => User::factory()->create()->id,
        'approved_at' => now(),
    ]);

    StockAdjustmentItem::factory()->create([
        'stock_adjustment_id' => $adjustment->id,
        'product_id' => $product->id,
        'quantity_adjusted' => $quantity,
    ]);

    /** @var StockAdjustment $adjustment */
    $adjustment = StockAdjustment::query()->findOrFail($adjustment->id);
    app(StockPostingService::class)->postStockAdjustment($adjustment);

    return $adjustment;
}

/**
 * Edit item penyesuaian approved: pembalik bertanggal movement asli, lalu item baru
 * diposting ulang (alur yang sama dengan StockAdjustmentController::update).
 */
function issue372EditAdjustment(StockAdjustment $adjustment, Product $product, float $quantity): void
{
    $service = app(StockPostingService::class);

    $repost = function () use ($adjustment, $product, $quantity, $service): void {
        $adjustment->items()->delete();
        StockAdjustmentItem::factory()->create([
            'stock_adjustment_id' => $adjustment->id,
            'product_id' => $product->id,
            'quantity_adjusted' => $quantity,
        ]);

        $service->postStockAdjustment($adjustment->refresh());
    };

    $service->editDocument(StockAdjustment::class, $adjustment->id, 'adjustment', $repost);
}

/**
 * Menutup tahun fiskal yang memuat $date (tahun fiskal dibuat jika belum ada).
 */
function closeFiscalYearContaining(CarbonInterface $date): void
{
    $fiscalYear = FiscalYear::query()
        ->where('start_date', '<=', $date->toDateString())
        ->where('end_date', '>=', $date->toDateString())
        ->first();

    if ($fiscalYear === null) {
        $fiscalYear = FiscalYear::factory()->create([
            'start_date' => $date->copy()->startOfYear()->toDateString(),
            'end_date' => $date->copy()->endOfYear()->toDateString(),
        ]);
    }

    $fiscalYear->update(['status' => 'closed']);
}

function issue372Reversal(GoodsReceipt $receipt): StockMovement
{
    return StockMovement::query()
        ->where('movement_type', 'goods_receipt_reversal')
        ->where('reference_type', GoodsReceipt::class)
        ->where('reference_id', $receipt->id)
        ->latest('id')
        ->firstOrFail();
}

describe('Issue #372 — edit: pembalik bertanggal movement asli', function () {
    test('edit GR 4 menjadi 3 membuat pembalik bertanggal tanggal GR, bukan hari edit', function () {
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $receiptDate = now()->subDays(10);

        $receipt = issue372GoodsReceipt($warehouse, $product, 4, $receiptDate);
        issue372EditGoodsReceipt($receipt, $product, 3);

        expect(issue372Reversal($receipt)->moved_at->toDateString())
            ->toBe($receiptDate->toDateString());
        expect(issue372BalanceOn($product, $warehouse, now()->subDays(5)))->toBe(3.0);
        expect(issue372BalanceOn($product, $warehouse, now()))->toBe(3.0);
    });

    test('edit berulang 4 -> 3 -> 2 tetap bertanggal GR dan saldo akhir 2', function () {
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $receiptDate = now()->subDays(10);

        $receipt = issue372GoodsReceipt($warehouse, $product, 4, $receiptDate);
        issue372EditGoodsReceipt($receipt, $product, 3);
        issue372EditGoodsReceipt($receipt, $product, 2);

        $reversals = StockMovement::query()
            ->where('reference_type', GoodsReceipt::class)
            ->where('reference_id', $receipt->id)
            ->where('movement_type', 'goods_receipt_reversal')
            ->get();

        expect($reversals)->toHaveCount(2);

        foreach ($reversals as $reversal) {
            expect($reversal->moved_at->toDateString())->toBe($receiptDate->toDateString());
        }

        expect(issue372BalanceOn($product, $warehouse, now()->subDays(5)))->toBe(2.0);
        expect(issue372BalanceOn($product, $warehouse, now()))->toBe(2.0);
    });

    test('edit yang membuat saldo negatif ditolak 422 tanpa menyisakan pembalik', function () {
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        // GR 4 (10 hari lalu), keluar 3 (5 hari lalu). Edit GR menjadi 2 membuat
        // saldo di tanggal keluar = 2 - 3 = -1 -> harus ditolak.
        $receipt = issue372GoodsReceipt($warehouse, $product, 4, now()->subDays(10));
        issue372Outflow($warehouse, $product, 3, now()->subDays(5));

        $thrown = null;

        try {
            issue372EditGoodsReceipt($receipt, $product, 2);
        } catch (ValidationException $e) {
            $thrown = $e;
        }

        expect($thrown)->toBeInstanceOf(ValidationException::class);
        expect($thrown?->status)->toBe(422);
        expect(StockMovement::query()->where('movement_type', 'goods_receipt_reversal')->count())->toBe(0);
        expect(issue372BalanceOn($product, $warehouse, now()))->toBe(1.0);
    });

    test('edit yang tidak membuat saldo negatif lolos walau ada pengeluaran setelahnya', function () {
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $receipt = issue372GoodsReceipt($warehouse, $product, 4, now()->subDays(10));
        issue372Outflow($warehouse, $product, 3, now()->subDays(5));

        issue372EditGoodsReceipt($receipt, $product, 4);

        expect(issue372BalanceOn($product, $warehouse, now()->subDays(5)))->toBe(1.0);
        expect(issue372BalanceOn($product, $warehouse, now()))->toBe(1.0);
    });
});

describe('Issue #372 — batal: pembalik bertanggal tanggal pembatalan', function () {
    test('batal GR membuat pembalik bertanggal hari ini dan riwayat sebelumnya tetap', function () {
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $receiptDate = now()->subDays(10);

        $receipt = issue372GoodsReceipt($warehouse, $product, 4, $receiptDate);

        app(StockPostingService::class)->reverseDocument(GoodsReceipt::class, $receipt->id, 'goods_receipt');

        expect(issue372Reversal($receipt)->moved_at->toDateString())->toBe(now()->toDateString());
        expect(issue372BalanceOn($product, $warehouse, now()->subDays(5)))->toBe(4.0);
        expect(issue372BalanceOn($product, $warehouse, now()))->toBe(0.0);
    });
});

describe('Issue #372 — langkah 2: batal supplier return dan stock transfer bertanggal hari batal', function () {
    test('batal supplier return confirmed membuat pembalik bertanggal hari ini, riwayat tetap', function () {
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $returnDate = now()->subDays(10);

        /** @var SupplierReturn $supplierReturn */
        $supplierReturn = SupplierReturn::factory()->create([
            'warehouse_id' => $warehouse->id,
            'return_date' => $returnDate->toDateString(),
            'status' => 'confirmed',
            'confirmed_by' => User::factory()->create()->id,
            'confirmed_at' => now(),
        ]);
        // Stok awal 5 agar retur 2 tidak membuat saldo negatif.
        issue372GoodsReceipt($warehouse, $product, 5, now()->subDays(20));
        SupplierReturnItem::factory()->create([
            'supplier_return_id' => $supplierReturn->id,
            'product_id' => $product->id,
            'quantity_returned' => 2,
        ]);

        app(StockPostingService::class)->postSupplierReturn($supplierReturn->refresh());
        app(StockPostingService::class)->reverseDocument(SupplierReturn::class, $supplierReturn->id, 'supplier_return');

        $reversal = StockMovement::query()
            ->where('movement_type', 'supplier_return_reversal')
            ->where('reference_type', SupplierReturn::class)
            ->where('reference_id', $supplierReturn->id)
            ->firstOrFail();

        expect($reversal->moved_at->toDateString())->toBe(now()->toDateString());
        expect(issue372BalanceOn($product, $warehouse, now()->subDays(5)))->toBe(3.0);
        expect(issue372BalanceOn($product, $warehouse, now()))->toBe(5.0);
    });

    test('batal stock transfer dispatched membuat pembalik bertanggal hari ini', function () {
        $product = Product::factory()->create();
        $from = Warehouse::factory()->create();
        $to = Warehouse::factory()->create();
        $transferDate = now()->subDays(10);

        issue372GoodsReceipt($from, $product, 4, now()->subDays(20));

        $transfer = StockTransfer::factory()->create([
            'from_warehouse_id' => $from->id,
            'to_warehouse_id' => $to->id,
            'transfer_date' => $transferDate->toDateString(),
            'status' => 'in_transit',
        ]);
        StockTransferItem::factory()->create([
            'stock_transfer_id' => $transfer->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        app(StockPostingService::class)->postStockTransferDispatch($transfer->refresh());
        app(StockPostingService::class)->reverseTransfer($transfer->refresh());

        $reversal = StockMovement::query()
            ->where('movement_type', 'transfer_out_reversal')
            ->where('reference_type', StockTransfer::class)
            ->where('reference_id', $transfer->id)
            ->firstOrFail();

        expect($reversal->moved_at->toDateString())->toBe(now()->toDateString());
        expect(issue372BalanceOn($product, $from, now()->subDays(5)))->toBe(1.0);
        expect(issue372BalanceOn($product, $from, now()))->toBe(4.0);
    });
});

describe('Issue #372 — langkah 2: edit dan batal stock adjustment, supplier return, stock transfer', function () {
    test('edit stock adjustment approved membuat pembalik bertanggal tanggal adjustment, bukan hari edit', function () {
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $adjustmentDate = now()->subDays(10);

        $adjustment = issue372Adjustment($warehouse, $product, 4, $adjustmentDate);
        issue372EditAdjustment($adjustment, $product, 3);

        $reversal = StockMovement::query()
            ->where('movement_type', 'adjustment_reversal')
            ->where('reference_type', StockAdjustment::class)
            ->where('reference_id', $adjustment->id)
            ->latest('id')
            ->firstOrFail();

        expect($reversal->moved_at->toDateString())->toBe($adjustmentDate->toDateString());
        expect(issue372BalanceOn($product, $warehouse, now()->subDays(5)))->toBe(3.0);
        expect(issue372BalanceOn($product, $warehouse, now()))->toBe(3.0);
    });

    test('edit stock adjustment yang menyentuh periode tahun fiskal tertutup ditolak 422', function () {
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $adjustmentDate = now()->subYears(2);

        $adjustment = issue372Adjustment($warehouse, $product, 4, $adjustmentDate);
        closeFiscalYearContaining($adjustmentDate);

        $thrown = null;

        try {
            issue372EditAdjustment($adjustment, $product, 3);
        } catch (ValidationException $e) {
            $thrown = $e;
        }

        expect($thrown)->toBeInstanceOf(ValidationException::class);
        expect($thrown?->status)->toBe(422);
        expect(StockMovement::query()->where('movement_type', 'adjustment_reversal')->count())->toBe(0);
    });
});

describe('Issue #372 — langkah 2: jalur HTTP edit dan batal memakai tanggal yang benar', function () {
    test('batal stock transfer lewat API membuat pembalik bertanggal hari batal', function () {
        Sanctum::actingAs(
            createTestUserWithPermissions(['stock_transfer', 'stock_transfer.delete']),
            ['*'],
        );

        $product = Product::factory()->create();
        $from = Warehouse::factory()->create();
        $to = Warehouse::factory()->create();
        $transferDate = now()->subDays(10);

        issue372GoodsReceipt($from, $product, 4, now()->subDays(20));

        $transfer = StockTransfer::factory()->create([
            'from_warehouse_id' => $from->id,
            'to_warehouse_id' => $to->id,
            'transfer_date' => $transferDate->toDateString(),
            'status' => 'in_transit',
        ]);
        StockTransferItem::factory()->create([
            'stock_transfer_id' => $transfer->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
        app(StockPostingService::class)->postStockTransferDispatch($transfer->refresh());

        deleteJson('/api/stock-transfers/' . $transfer->id)->assertNoContent();

        $reversal = StockMovement::query()
            ->where('movement_type', 'transfer_out_reversal')
            ->where('reference_type', StockTransfer::class)
            ->where('reference_id', $transfer->id)
            ->firstOrFail();

        expect($reversal->moved_at->toDateString())->toBe(now()->toDateString());
        expect(issue372BalanceOn($product, $from, now()->subDays(5)))->toBe(1.0);
        expect(issue372BalanceOn($product, $from, now()))->toBe(4.0);
    });

    test('edit stock adjustment yang membuat saldo berjalan negatif setelah pengeluaran ditolak 422', function () {
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $adjustment = issue372Adjustment($warehouse, $product, 4, now()->subDays(10));
        issue372Outflow($warehouse, $product, 3, now()->subDays(5));

        $thrown = null;

        try {
            issue372EditAdjustment($adjustment, $product, 2);
        } catch (ValidationException $e) {
            $thrown = $e;
        }

        expect($thrown)->toBeInstanceOf(ValidationException::class);
        expect($thrown?->status)->toBe(422);
        expect(issue372BalanceOn($product, $warehouse, now()))->toBe(1.0);
        expect(StockMovement::query()->where('movement_type', 'adjustment_reversal')->count())->toBe(0);
    });
});

describe('Issue #372 — langkah 2: batal transfer di periode tertutup', function () {
    test('batal stock transfer saat tahun fiskal hari batal tertutup ditolak 422 tanpa pembalik', function () {
        $product = Product::factory()->create();
        $from = Warehouse::factory()->create();
        $to = Warehouse::factory()->create();

        issue372GoodsReceipt($from, $product, 4, now()->subDays(20));

        $transfer = StockTransfer::factory()->create([
            'from_warehouse_id' => $from->id,
            'to_warehouse_id' => $to->id,
            'transfer_date' => now()->subDays(10)->toDateString(),
            'status' => 'in_transit',
        ]);
        StockTransferItem::factory()->create([
            'stock_transfer_id' => $transfer->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
        app(StockPostingService::class)->postStockTransferDispatch($transfer->refresh());

        closeFiscalYearContaining(now());

        $thrown = null;

        try {
            app(StockPostingService::class)->reverseTransfer($transfer->refresh());
        } catch (ValidationException $e) {
            $thrown = $e;
        }

        expect($thrown)->toBeInstanceOf(ValidationException::class);
        expect($thrown?->status)->toBe(422);
        expect(StockMovement::query()->where('movement_type', 'transfer_out_reversal')->count())->toBe(0);
    });
});

describe('Issue #372 — dua gudang tidak saling memengaruhi saldo', function () {
    test('edit GR gudang A tidak mengubah saldo gudang B', function () {
        $product = Product::factory()->create();
        $warehouseA = Warehouse::factory()->create();
        $warehouseB = Warehouse::factory()->create();
        $receiptDate = now()->subDays(10);

        $receiptA = issue372GoodsReceipt($warehouseA, $product, 4, $receiptDate);
        issue372GoodsReceipt($warehouseB, $product, 6, $receiptDate);

        issue372EditGoodsReceipt($receiptA, $product, 1);

        expect(issue372BalanceOn($product, $warehouseB, now()->subDays(5)))->toBe(6.0);
        expect(issue372BalanceOn($product, $warehouseB, now()))->toBe(6.0);
        expect(issue372BalanceOn($product, $warehouseA, now()->subDays(5)))->toBe(1.0);
        expect(issue372BalanceOn($product, $warehouseA, now()))->toBe(1.0);
    });
});

/**
 * Movement mentah (tanpa dokumen) untuk menyiapkan riwayat saldo negatif lama,
 * misalnya data sebelum #356. Saldo dihitung ulang setelah dibuat.
 *
 * @param  array<string, mixed>  $attributes
 */
function issue372RawMovement(
    Product $product,
    Warehouse $warehouse,
    float $quantityIn,
    float $quantityOut,
    CarbonInterface $movedAt,
    array $attributes = [],
): StockMovement {
    $movement = StockMovement::factory()->create($attributes + [
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'movement_type' => $quantityOut > 0 ? 'sales' : 'adjustment_in',
        'quantity_in' => $quantityIn,
        'quantity_out' => $quantityOut,
        'reference_type' => null,
        'reference_id' => null,
        'reference_number' => null,
        'moved_at' => $movedAt,
    ]);

    app(StockPostingService::class)->rebuildBalances([[$product->id, $warehouse->id]]);

    return $movement;
}

describe('Issue #372 — review #420: riwayat saldo negatif lama tidak memblokir, perburukan ditolak', function () {
    test('batal penyesuaian keluar pada riwayat yang sudah negatif lolos bila tidak memperburuk', function () {
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        // Riwayat negatif lama: keluar 3 pada 15 hari lalu (saldo -3).
        issue372RawMovement($product, $warehouse, 0, 3, now()->subDays(15));

        // Penyesuaian keluar 2 pada 5 hari lalu (saldo -5), lalu dibatalkan hari ini.
        /** @var StockAdjustment $adjustment */
        $adjustment = StockAdjustment::factory()->create(['warehouse_id' => $warehouse->id]);
        issue372RawMovement($product, $warehouse, 0, 2, now()->subDays(5), [
            'movement_type' => 'adjustment_out',
            'reference_type' => StockAdjustment::class,
            'reference_id' => $adjustment->id,
        ]);

        app(StockPostingService::class)->reverseDocument(StockAdjustment::class, $adjustment->id, 'adjustment');

        expect(issue372BalanceOn($product, $warehouse, now()))->toBe(-3.0);
    });

    test('edit yang menaikkan stok pada riwayat yang sudah negatif lolos', function () {
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        issue372RawMovement($product, $warehouse, 0, 3, now()->subDays(15));
        $receipt = issue372GoodsReceipt($warehouse, $product, 4, now()->subDays(5));

        issue372EditGoodsReceipt($receipt, $product, 5);

        expect(issue372BalanceOn($product, $warehouse, now()))->toBe(2.0);
    });

    test('edit yang memperburuk saldo negatif lama ditolak 422 tanpa menyisakan pembalik', function () {
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        // Riwayat negatif lama: keluar 3 pada 15 hari lalu (saldo -3). GR 4 pada 10 hari
        // lalu (saldo 1), keluar 2 pada 5 hari lalu (saldo -1). Edit GR menjadi 0 membuat
        // saldo -5 pada 5 hari lalu, lebih buruk dari riwayat -3 -> harus ditolak.
        issue372RawMovement($product, $warehouse, 0, 3, now()->subDays(15));
        $receipt = issue372GoodsReceipt($warehouse, $product, 4, now()->subDays(10));
        issue372Outflow($warehouse, $product, 2, now()->subDays(5));

        $thrown = null;

        try {
            issue372EditGoodsReceipt($receipt, $product, 0);
        } catch (ValidationException $e) {
            $thrown = $e;
        }

        expect($thrown)->toBeInstanceOf(ValidationException::class);
        expect($thrown?->status)->toBe(422);
        expect(StockMovement::query()->where('movement_type', 'goods_receipt_reversal')->count())->toBe(0);
    });

    test('batal GR yang membuat saldo berjalan menjadi lebih negatif dari riwayat lama ditolak 422', function () {
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        issue372RawMovement($product, $warehouse, 0, 3, now()->subDays(15));
        $receipt = issue372GoodsReceipt($warehouse, $product, 4, now()->subDays(10));
        issue372Outflow($warehouse, $product, 2, now()->subDays(5));

        $thrown = null;

        try {
            app(StockPostingService::class)->reverseDocument(GoodsReceipt::class, $receipt->id, 'goods_receipt');
        } catch (ValidationException $e) {
            $thrown = $e;
        }

        expect($thrown)->toBeInstanceOf(ValidationException::class);
        expect($thrown?->status)->toBe(422);
        expect(StockMovement::query()->where('movement_type', 'goods_receipt_reversal')->count())->toBe(0);
    });
});
