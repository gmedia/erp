<?php

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockPostingService;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class)->group('stock-posting-issue-372');

/**
 * GR confirmed bertanggal $receiptDate dengan satu baris $quantity, diposting.
 */
function issue372ReportGoodsReceipt(
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

    app(StockPostingService::class)->postGoodsReceipt($receipt->refresh());

    return $receipt->refresh();
}

/**
 * Edit item GR dengan pola yang sama seperti GoodsReceiptController::update.
 */
function issue372ReportEditGoodsReceipt(GoodsReceipt $receipt, Product $product, float $quantity): void
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
 * Saldo berjalan pada akhir hari $date, sama dengan aturan laporan stok per tanggal.
 */
function issue372ReportBalanceOn(Product $product, Warehouse $warehouse, CarbonInterface $date): float
{
    return (float) StockMovement::query()
        ->where('product_id', $product->id)
        ->where('warehouse_id', $warehouse->id)
        ->where('moved_at', '<=', $date->copy()->endOfDay())
        ->orderByDesc('moved_at')
        ->orderByDesc('id')
        ->value('balance_after');
}

describe('Issue #372 — laporan stok per tanggal setelah edit GR', function () {
    test('saldo per tanggal sebelum dan sesudah edit tetap benar, bukan saldo hari edit', function () {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();
        $receiptDate = now()->subDays(10);

        $receipt = issue372ReportGoodsReceipt($warehouse, $product, 4, $receiptDate);
        issue372ReportEditGoodsReceipt($receipt, $product, 3);

        // Di antara tanggal GR dan hari edit, stok harus 3 (bukan 7 dari pembalik salah tanggal).
        expect(issue372ReportBalanceOn($product, $warehouse, now()->subDays(5)))->toBe(3.0)
            ->and(issue372ReportBalanceOn($product, $warehouse, now()))->toBe(3.0);
    });

    test('laporan stok per tanggal lewat API mengembalikan saldo akhir sesuai end_date', function () {
        $user = createTestUserWithPermissions(['stock_movement_report']);
        Sanctum::actingAs($user, ['*']);

        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();
        $receiptDate = now()->subDays(10);

        $receipt = issue372ReportGoodsReceipt($warehouse, $product, 4, $receiptDate);
        issue372ReportEditGoodsReceipt($receipt, $product, 3);

        $endDate = now()->subDays(5)->toDateString();

        $this->getJson('/api/reports/stock-movement?end_date=' . $endDate . '&warehouse_id=' . $warehouse->id)
            ->assertOk()
            ->assertJsonPath('data.0.ending_balance', '3.00');
    });

    test('valuasi memakai saldo dan nilai akhir setelah edit GR, tanpa pembalik hari edit', function () {
        $user = createTestUserWithPermissions(['inventory_valuation_report']);
        Sanctum::actingAs($user, ['*']);

        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create(['cost' => 1000]);
        $receiptDate = now()->subDays(10);

        $receipt = issue372ReportGoodsReceipt($warehouse, $product, 4, $receiptDate);
        issue372ReportEditGoodsReceipt($receipt, $product, 3);

        $this->getJson('/api/reports/inventory-valuation?warehouse_id=' . $warehouse->id)
            ->assertOk()
            ->assertJsonPath('data.0.quantity_on_hand', '3.00');
    });

    test('kartu stok urut dan saldo berjalan tidak negatif setelah edit GR', function () {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();
        $receiptDate = now()->subDays(10);

        $receipt = issue372ReportGoodsReceipt($warehouse, $product, 4, $receiptDate);
        issue372ReportEditGoodsReceipt($receipt, $product, 3);

        $ledger = StockMovement::query()
            ->where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->orderBy('moved_at')
            ->orderBy('id')
            ->get();

        expect($ledger)->not->toBeEmpty()
            ->and($ledger->every(fn (StockMovement $m) => (float) $m->balance_after >= 0))->toBeTrue()
            ->and((float) $ledger->last()->balance_after)->toBe(3.0);
    });
});
