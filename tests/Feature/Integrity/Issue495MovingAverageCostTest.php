<?php

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockPostingService;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class)->group('stock-movements');

/**
 * GR confirmed (diposting langsung) dengan satu item $quantity @ $unitPrice.
 */
function issue495Receipt(Warehouse $warehouse, Product $product, float $quantity, float $unitPrice, string $date): void
{
    $purchaseOrder = PurchaseOrder::factory()->create(['warehouse_id' => $warehouse->id]);
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
        'unit_price' => $unitPrice,
    ]);

    app(StockPostingService::class)->postGoodsReceipt($receipt->refresh());
}

/**
 * Penyesuaian stok approved bertanggal $date dengan selisih $quantity (negatif = keluar).
 */
function issue495Adjustment(Warehouse $warehouse, Product $product, float $quantity, CarbonInterface $date): void
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
        'quantity_adjusted' => $quantity,
    ]);

    app(StockPostingService::class)->postStockAdjustment($adjustment->refresh());
}

/**
 * Movement terakhir (moved_at, id) untuk scope produk+gudang.
 */
function issue495LastMovement(Product $product, Warehouse $warehouse): StockMovement
{
    return StockMovement::query()
        ->where('product_id', $product->id)
        ->where('warehouse_id', $warehouse->id)
        ->orderByDesc('moved_at')
        ->orderByDesc('id')
        ->firstOrFail();
}

test('movement masuk menghitung rata-rata tertimbang bergerak dari dua GR beda harga', function () {
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create(['cost' => 0]);

    issue495Receipt($warehouse, $product, 10, 1000, '2026-01-10');
    issue495Receipt($warehouse, $product, 10, 2000, '2026-01-11');

    $movements = StockMovement::query()
        ->where('product_id', $product->id)
        ->where('warehouse_id', $warehouse->id)
        ->orderBy('moved_at')
        ->orderBy('id')
        ->get();

    expect($movements)->toHaveCount(2)
        ->and((float) $movements[0]->average_cost_after)->toBe(1000.0)
        ->and((float) $movements[1]->average_cost_after)->toBe(1500.0)
        ->and((float) $movements[1]->balance_after)->toBe(20.0);
});

test('movement keluar dinilai dengan rata-rata berjalan dan rata-rata tidak berubah', function () {
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create(['cost' => 0]);

    issue495Receipt($warehouse, $product, 10, 1000, '2026-01-10');
    issue495Receipt($warehouse, $product, 10, 2000, '2026-01-11');
    issue495Adjustment($warehouse, $product, -5, Carbon::parse('2026-01-12'));

    $outflow = issue495LastMovement($product, $warehouse);

    expect($outflow->movement_type)->toBe('adjustment_out')
        ->and((float) $outflow->quantity_out)->toBe(5.0)
        ->and((float) $outflow->unit_cost)->toBe(1500.0)
        ->and((float) $outflow->average_cost_after)->toBe(1500.0)
        ->and((float) $outflow->balance_after)->toBe(15.0);
});

test('nilai persediaan laporan valuasi sama dengan qty dikali rata-rata bergerak', function () {
    $user = createTestUserWithPermissions(['inventory_valuation_report']);
    $warehouse = Warehouse::factory()->create(['name' => 'Gudang Avg']);
    $product = Product::factory()->create(['name' => 'Produk Rata Rata 495', 'cost' => 5000]);

    issue495Receipt($warehouse, $product, 10, 1000, '2026-01-10');
    issue495Receipt($warehouse, $product, 10, 2000, '2026-01-11');
    issue495Adjustment($warehouse, $product, -5, Carbon::parse('2026-01-12'));

    Sanctum::actingAs($user, ['*']);

    $row = collect($this->getJson('/api/reports/inventory-valuation')->assertOk()->json('data'))
        ->firstWhere('product.name', 'Produk Rata Rata 495');

    expect($row)->not->toBeNull()
        ->and($row['quantity_on_hand'])->toBe('15.00')
        ->and($row['average_cost'])->toBe('1500.00')
        ->and($row['stock_value'])->toBe('22500.00');
});

test('dispatch transfer keluar memakai rata-rata gudang asal dan tidak mengubahnya', function () {
    $source = Warehouse::factory()->create();
    $destination = Warehouse::factory()->create();
    $product = Product::factory()->create(['cost' => 0]);

    issue495Receipt($source, $product, 10, 1000, '2026-01-10');
    issue495Receipt($source, $product, 10, 2000, '2026-01-11');

    $transfer = StockTransfer::factory()->create([
        'from_warehouse_id' => $source->id,
        'to_warehouse_id' => $destination->id,
        'transfer_date' => '2026-01-13',
        'status' => 'in_transit',
    ]);
    StockTransferItem::factory()->create([
        'stock_transfer_id' => $transfer->id,
        'product_id' => $product->id,
        'quantity' => 4,
    ]);

    app(StockPostingService::class)->postStockTransferDispatch($transfer->refresh());

    $dispatch = StockMovement::query()
        ->where('movement_type', 'transfer_out')
        ->where('reference_id', $transfer->id)
        ->firstOrFail();

    expect((float) $dispatch->unit_cost)->toBe(1500.0)
        ->and((float) $dispatch->average_cost_after)->toBe(1500.0)
        ->and((float) $dispatch->balance_after)->toBe(16.0);
});

test('penerimaan transfer menghitung rata-rata tujuan dengan biaya kirim', function () {
    $source = Warehouse::factory()->create();
    $destination = Warehouse::factory()->create();
    $product = Product::factory()->create(['cost' => 0]);

    issue495Receipt($destination, $product, 5, 1000, '2026-01-10');

    $transfer = StockTransfer::factory()->create([
        'from_warehouse_id' => $source->id,
        'to_warehouse_id' => $destination->id,
        'transfer_date' => '2026-01-12',
        'status' => 'received',
        'received_at' => '2026-01-12 09:00:00',
    ]);
    StockTransferItem::factory()->create([
        'stock_transfer_id' => $transfer->id,
        'product_id' => $product->id,
        'quantity' => 5,
        'quantity_received' => 5,
        'unit_cost' => 2000,
    ]);

    app(StockPostingService::class)->postStockTransferReceive($transfer->refresh());

    $receive = StockMovement::query()
        ->where('movement_type', 'transfer_in')
        ->where('reference_id', $transfer->id)
        ->firstOrFail();

    expect((float) $receive->average_cost_after)->toBe(1500.0)
        ->and((float) $receive->balance_after)->toBe(10.0)
        ->and((float) issue495LastMovement($product, $destination)->average_cost_after)->toBe(1500.0);
});
