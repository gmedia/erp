<?php

use App\Models\FiscalYear;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PeriodClosing;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class)->group('stock-period-lock-issue-434');

/**
 * Issue #434: perubahan stok bertanggal di periode tertutup (monthly/annual) ditolak 422,
 * memakai guard yang sama dengan jurnal (#423). Penolakan terjadi sebelum ada yang tersimpan.
 */
function issue434FiscalYear(): FiscalYear
{
    return FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);
}

function issue434CloseMonth(FiscalYear $fiscalYear, int $month): void
{
    PeriodClosing::factory()->closed()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'closing_type' => 'monthly',
        'period_year' => 2026,
        'period_month' => $month,
    ]);
}

function issue434Receipt(Warehouse $warehouse, Product $product, float $quantity, string $date): GoodsReceipt
{
    /** @var PurchaseOrder $purchaseOrder */
    $purchaseOrder = PurchaseOrder::factory()->create(['warehouse_id' => $warehouse->id]);
    /** @var GoodsReceipt $receipt */
    $receipt = GoodsReceipt::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'warehouse_id' => $warehouse->id,
        'receipt_date' => $date,
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

    return GoodsReceipt::query()->findOrFail($receipt->id);
}

function issue434Adjustment(Warehouse $warehouse, Product $product, float $quantity, string $date): StockAdjustment
{
    /** @var StockAdjustment $adjustment */
    $adjustment = StockAdjustment::factory()->create([
        'warehouse_id' => $warehouse->id,
        'adjustment_date' => $date,
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

    return StockAdjustment::query()->findOrFail($adjustment->id);
}

test('movement GR baru bertanggal di bulan tertutup ditolak 422 tanpa tersimpan', function () {
    $fiscalYear = issue434FiscalYear();
    issue434CloseMonth($fiscalYear, 3);
    $product = Product::factory()->create();
    $warehouse = Warehouse::factory()->create();
    $receipt = issue434Receipt($warehouse, $product, 5, '2026-03-15');

    expect(fn () => app(StockPostingService::class)->postGoodsReceipt($receipt))
        ->toThrow(ValidationException::class);
    expect(StockMovement::query()->where('reference_id', $receipt->id)->count())->toBe(0);
});

test('penyesuaian stok bertanggal di bulan tertutup ditolak 422', function () {
    $fiscalYear = issue434FiscalYear();
    issue434CloseMonth($fiscalYear, 4);
    $product = Product::factory()->create();
    $warehouse = Warehouse::factory()->create();
    $adjustment = issue434Adjustment($warehouse, $product, 2, '2026-04-10');

    expect(fn () => app(StockPostingService::class)->postStockAdjustment($adjustment))
        ->toThrow(ValidationException::class);
    expect(StockMovement::query()->where('reference_id', $adjustment->id)->count())->toBe(0);
});

test('bulan terbuka menerima movement stok', function () {
    $fiscalYear = issue434FiscalYear();
    issue434CloseMonth($fiscalYear, 3);
    $product = Product::factory()->create();
    $warehouse = Warehouse::factory()->create();
    $receipt = issue434Receipt($warehouse, $product, 5, '2026-05-15');

    app(StockPostingService::class)->postGoodsReceipt($receipt);

    expect(StockMovement::query()->where('reference_id', $receipt->id)->count())->toBe(1);
});

test('periode yang dibuka kembali (reopened) menerima movement stok', function () {
    $fiscalYear = issue434FiscalYear();
    PeriodClosing::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'closing_type' => 'monthly',
        'period_year' => 2026,
        'period_month' => 6,
        'status' => 'reopened',
    ]);
    $product = Product::factory()->create();
    $warehouse = Warehouse::factory()->create();
    $receipt = issue434Receipt($warehouse, $product, 5, '2026-06-15');

    app(StockPostingService::class)->postGoodsReceipt($receipt);

    expect(StockMovement::query()->where('reference_id', $receipt->id)->count())->toBe(1);
});
