<?php

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockPostingService;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

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

    $receipt = $receipt->refresh();
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

    $adjustment = $adjustment->refresh();
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
