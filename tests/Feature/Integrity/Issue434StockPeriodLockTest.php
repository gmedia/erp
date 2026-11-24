<?php

use App\Models\FiscalYear;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\JournalEntry;
use App\Models\PeriodClosing;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\SupplierReturn;
use App\Models\SupplierReturnItem;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\putJson;

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
    $unit = Unit::factory()->create();

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

    /** @var PurchaseOrderItem $purchaseItem */
    $purchaseItem = $purchaseOrder->items()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'quantity' => $quantity,
        'unit_price' => 5000,
        'discount_percent' => 0,
        'tax_percent' => 0,
        'line_total' => $quantity * 5000,
    ]);

    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $receipt->id,
        'purchase_order_item_id' => $purchaseItem->id,
        'unit_id' => $unit->id,
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

/**
 * Snapshot keadaan yang tidak boleh berubah saat request ditolak 422.
 *
 * @return array<string, mixed>
 */
function issue434Snapshot(int $productId, int $warehouseId): array
{
    return [
        'movement_count' => StockMovement::query()->count(),
        'balance' => (float) StockMovement::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->orderByDesc('moved_at')
            ->orderByDesc('id')
            ->value('balance_after'),
        'journal_entries' => JournalEntry::query()->count(),
    ];
}

test('edit GR confirmed yang movement aslinya di bulan tertutup ditolak 422 tanpa perubahan', function () {
    Sanctum::actingAs(createTestUserWithPermissions(['goods_receipt', 'goods_receipt.edit']));
    createAccountingControlFixture();

    // Tahun fiskal dari fixture COA saja: membuat tahun kedua yang menimpa tanggal yang sama
    // membuat guard memilih tahun tanpa penutupan (fixture bisa membuat 2026 juga).
    $fiscalYear = FiscalYear::query()->where('name', (string) now()->year)->firstOrFail();
    $product = Product::factory()->create();
    $warehouse = Warehouse::factory()->create();
    $receipt = issue434Receipt($warehouse, $product, 5, '2026-03-15');
    app(StockPostingService::class)->postGoodsReceipt($receipt);

    issue434CloseMonth($fiscalYear, 3);
    $before = issue434Snapshot($product->id, $warehouse->id);
    $itemId = $receipt->items()->value('id');

    putJson('/api/goods-receipts/' . $receipt->id, [
        'status' => 'confirmed',
        'items' => [[
            'id' => $itemId,
            'purchase_order_item_id' => $receipt->items()->value('purchase_order_item_id'),
            'product_id' => $product->id,
            'unit_id' => $receipt->items()->value('unit_id'),
            'quantity_received' => 3,
            'quantity_accepted' => 3,
            'quantity_rejected' => 0,
            'unit_price' => 5000,
        ]],
    ])->assertStatus(422)->assertJsonValidationErrors(['entry_date']);

    expect(issue434Snapshot($product->id, $warehouse->id))->toBe($before);
    expect((float) $receipt->items()->value('quantity_accepted'))->toBe(5.0);
});

test('edit penyesuaian approved yang movement aslinya di bulan tertutup ditolak 422 tanpa perubahan', function () {
    Sanctum::actingAs(createTestUserWithPermissions(['stock_adjustment', 'stock_adjustment.edit']));

    $fiscalYear = issue434FiscalYear();
    $product = Product::factory()->create();
    $warehouse = Warehouse::factory()->create();
    $adjustment = issue434Adjustment($warehouse, $product, 2, '2026-04-10');
    app(StockPostingService::class)->postStockAdjustment($adjustment);

    issue434CloseMonth($fiscalYear, 4);
    $before = issue434Snapshot($product->id, $warehouse->id);

    putJson('/api/stock-adjustments/' . $adjustment->id, [
        'warehouse_id' => $warehouse->id,
        'adjustment_date' => '2026-04-10',
        'status' => 'approved',
        'items' => [[
            'product_id' => $product->id,
            'quantity_adjusted' => 7,
        ]],
    ])->assertStatus(422);

    expect(issue434Snapshot($product->id, $warehouse->id))->toBe($before);
});

test('retur pemasok bertanggal di bulan tertutup ditolak 422 tanpa movement', function () {
    $fiscalYear = issue434FiscalYear();
    issue434CloseMonth($fiscalYear, 5);
    $product = Product::factory()->create();
    $warehouse = Warehouse::factory()->create();
    /** @var SupplierReturn $supplierReturn */
    $supplierReturn = SupplierReturn::factory()->create([
        'warehouse_id' => $warehouse->id,
        'return_date' => '2026-05-12',
        'status' => 'confirmed',
    ]);
    SupplierReturnItem::factory()->create([
        'supplier_return_id' => $supplierReturn->id,
        'product_id' => $product->id,
        'quantity_returned' => 2,
    ]);
    $before = issue434Snapshot($product->id, $warehouse->id);

    expect(fn () => app(StockPostingService::class)->postSupplierReturn($supplierReturn->refresh()))
        ->toThrow(ValidationException::class);

    expect(issue434Snapshot($product->id, $warehouse->id))->toBe($before);
});

test('transfer dispatch bertanggal di bulan tertutup ditolak 422 tanpa movement', function () {
    $fiscalYear = issue434FiscalYear();
    issue434CloseMonth($fiscalYear, 7);
    $product = Product::factory()->create();
    $from = Warehouse::factory()->create();
    $to = Warehouse::factory()->create();
    $transfer = StockTransfer::factory()->create([
        'from_warehouse_id' => $from->id,
        'to_warehouse_id' => $to->id,
        'transfer_date' => '2026-07-08',
        'status' => 'in_transit',
    ]);
    StockTransferItem::factory()->create([
        'stock_transfer_id' => $transfer->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);
    $before = issue434Snapshot($product->id, $from->id);

    expect(fn () => app(StockPostingService::class)->postStockTransferDispatch($transfer->refresh()))
        ->toThrow(ValidationException::class);

    expect(issue434Snapshot($product->id, $from->id))->toBe($before);
});

test('pembatalan hari ini ditolak 422 bila bulan ini tertutup dan tidak mengubah movement', function () {
    $fiscalYear = issue434FiscalYear();
    issue434CloseMonth($fiscalYear, (int) now()->month);
    $product = Product::factory()->create();
    $warehouse = Warehouse::factory()->create();
    $receipt = issue434Receipt($warehouse, $product, 5, now()->subDays(40)->toDateString());
    // Posting di sini memakai tanggal lampau yang terbuka, lalu bulan ini ditutup.
    app(StockPostingService::class)->postGoodsReceipt($receipt);
    $before = issue434Snapshot($product->id, $warehouse->id);

    expect(fn () => app(StockPostingService::class)->reverseDocument(
        GoodsReceipt::class,
        $receipt->id,
        'goods_receipt',
    ))->toThrow(ValidationException::class);

    expect(issue434Snapshot($product->id, $warehouse->id))->toBe($before);
});

test('penutupan tahunan mengunci tanggal dalam tahun fiskal non-kalender', function () {
    // Tahun fiskal 1 Apr 2026 - 31 Mar 2027 (bukan kalender). Penutupan tahunan mengunci
    // seluruh tahun fiskal, sehingga 10 Jan 2027 ikut terkunci meski bukan tahun kalender yang sama.
    $fiscalYear = FiscalYear::factory()->create([
        'name' => 'FY 2026/2027',
        'start_date' => '2026-04-01',
        'end_date' => '2027-03-31',
    ]);
    PeriodClosing::factory()->annual()->closed()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'period_year' => 2026,
    ]);
    $product = Product::factory()->create();
    $warehouse = Warehouse::factory()->create();
    $receipt = issue434Receipt($warehouse, $product, 5, '2027-01-10');
    $before = issue434Snapshot($product->id, $warehouse->id);

    expect(fn () => app(StockPostingService::class)->postGoodsReceipt($receipt))
        ->toThrow(ValidationException::class);

    expect(issue434Snapshot($product->id, $warehouse->id))->toBe($before);
});

test('pembatalan transfer hari ini ditolak 422 bila bulan ini tertutup tanpa pembalik tersimpan', function () {
    $fiscalYear = issue434FiscalYear();
    $product = Product::factory()->create();
    $from = Warehouse::factory()->create();
    $to = Warehouse::factory()->create();
    // Stok awal di gudang asal agar dispatch tidak ditolak karena saldo.
    StockMovement::factory()->create([
        'product_id' => $product->id,
        'warehouse_id' => $from->id,
        'movement_type' => 'adjustment_in',
        'quantity_in' => 10,
        'quantity_out' => 0,
        'balance_after' => 10,
        'moved_at' => now()->subDays(60),
    ]);
    $transfer = StockTransfer::factory()->create([
        'from_warehouse_id' => $from->id,
        'to_warehouse_id' => $to->id,
        'transfer_date' => now()->subDays(40)->toDateString(),
        'status' => 'in_transit',
    ]);
    StockTransferItem::factory()->create([
        'stock_transfer_id' => $transfer->id,
        'product_id' => $product->id,
        'quantity' => 3,
    ]);
    // Dispatch dipost saat bulan ini masih terbuka, lalu bulan ini ditutup.
    app(StockPostingService::class)->postStockTransferDispatch($transfer->refresh());
    issue434CloseMonth($fiscalYear, (int) now()->month);
    $before = issue434Snapshot($product->id, $from->id);

    expect(fn () => app(StockPostingService::class)->reverseTransfer($transfer->refresh()))
        ->toThrow(ValidationException::class);

    expect(issue434Snapshot($product->id, $from->id))->toBe($before);
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
