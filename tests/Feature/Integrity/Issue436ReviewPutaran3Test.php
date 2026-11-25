<?php

use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\SupplierReturn;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\StockPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('issue-436-review-putaran-3');

/**
 * Issue #436, komentar "Review putaran 3" di PR #444:
 * - Butir 1: konfirmasi GR tanpa items harus mengecek accepted + rejected <= received
 *   dari item yang tersimpan (bukan hanya dari payload items).
 * - Butir 2: retur pemasok dan transfer kirim (create dan update, tanggal mundur) harus
 *   memeriksa saldo berjalan sejak tanggal dokumen, lewat assertNoNegativeStockFrom.
 */
beforeEach(function () {
    $user = createTestUserWithPermissions([
        'goods_receipt',
        'goods_receipt.create',
        'goods_receipt.edit',
        'supplier_return',
        'supplier_return.create',
        'supplier_return.edit',
        'stock_transfer',
        'stock_transfer.create',
        'stock_transfer.edit',
        'stock_adjustment',
        'stock_adjustment.create',
        'stock_adjustment.edit',
    ]);
    Sanctum::actingAs($user, ['*']);

    FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);

    $coaVersion = CoaVersion::factory()->create(['status' => 'active']);

    foreach ([
        ['11300', 'Persediaan', 'asset', 'debit'],
        ['21150', 'Barang Diterima Belum Ditagih', 'liability', 'credit'],
        ['51000', 'Selisih Persediaan', 'expense', 'debit'],
    ] as [$code, $name, $type, $normal]) {
        Account::factory()->create([
            'coa_version_id' => $coaVersion->id,
            'code' => $code,
            'name' => $name,
            'type' => $type,
            'normal_balance' => $normal,
            'is_active' => true,
        ]);
    }
});

/**
 * @return array{0: PurchaseOrder, 1: PurchaseOrderItem}
 */
function review3PoLine(float $qty): array
{
    $purchaseOrder = PurchaseOrder::factory()->create(['status' => 'confirmed']);
    $item = $purchaseOrder->items()->create([
        'product_id' => Product::factory()->create()->id,
        'unit_id' => Unit::factory()->create()->id,
        'quantity' => $qty,
        'unit_price' => 5000,
        'discount_percent' => 0,
        'tax_percent' => 0,
        'line_total' => 5000 * $qty,
    ]);

    return [$purchaseOrder, $item];
}

/**
 * GR draft (tanpa dikonfirmasi) dengan satu baris. Dikembalikan id GR.
 */
function review3DraftGr(
    PurchaseOrder $purchaseOrder,
    PurchaseOrderItem $item,
    float $received,
    float $accepted,
    float $rejected,
): int {
    $response = postJson('/api/goods-receipts', [
        'purchase_order_id' => $purchaseOrder->id,
        'warehouse_id' => $purchaseOrder->warehouse_id,
        'receipt_date' => now()->toDateString(),
        'status' => 'draft',
        'items' => [[
            'purchase_order_item_id' => $item->id,
            'product_id' => $item->product_id,
            'unit_id' => $item->unit_id,
            'quantity_received' => $received,
            'quantity_accepted' => $accepted,
            'quantity_rejected' => $rejected,
            'unit_price' => 5000,
        ]],
    ])->assertStatus(201);

    return (int) $response->json('data.id');
}

/**
 * Stok awal di gudang lewat penyesuaian masuk yang sudah approved.
 *
 * @return array{0: Warehouse, 1: Product, 2: Unit}
 */
function review3StockWithBalance(float $quantity, string $inboundDate): array
{
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create();
    $unit = Unit::factory()->create();

    $inbound = StockAdjustment::factory()->create([
        'warehouse_id' => $warehouse->id,
        'status' => 'approved',
        'adjustment_date' => $inboundDate,
    ]);
    StockAdjustmentItem::factory()->create([
        'stock_adjustment_id' => $inbound->id,
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'quantity_adjusted' => $quantity,
    ]);
    app(StockPostingService::class)->postStockAdjustment($inbound->refresh());

    return [$warehouse, $product, $unit];
}

test('butir 1: confirming a draft GR with status only rejects accepted + rejected above received', function () {
    // Draft tersimpan dengan accepted 9 + rejected 0 > received 4. Payload konfirmasi hanya status.
    [$purchaseOrder, $item] = review3PoLine(10);
    $draftId = review3DraftGr($purchaseOrder, $item, 4, 4, 0);
    GoodsReceiptItem::query()->where('goods_receipt_id', $draftId)->update(['quantity_accepted' => 9]);

    putJson('/api/goods-receipts/' . $draftId, ['status' => 'confirmed'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['items.0.quantity_accepted']);

    expect(GoodsReceipt::query()->findOrFail($draftId)->status)->toBe('draft');
    expect(StockMovement::query()
        ->where('reference_type', GoodsReceipt::class)
        ->where('reference_id', $draftId)
        ->count())->toBe(0);
});

test('butir 1: confirming a draft GR with status only is accepted when stored items are valid', function () {
    [$purchaseOrder, $item] = review3PoLine(10);
    $draftId = review3DraftGr($purchaseOrder, $item, 4, 3, 1);

    putJson('/api/goods-receipts/' . $draftId, ['status' => 'confirmed'])
        ->assertOk();

    expect(GoodsReceipt::query()->findOrFail($draftId)->status)->toBe('confirmed');
});

test('butir 2: supplier return dated before a negative running balance is rejected with 422', function () {
    // Masuk 10 tiga hari lalu, lalu retur 5 bertanggal delapan hari lalu (sebelum masuk).
    [$purchaseOrder, $item] = review3PoLine(100);
    $draftId = review3DraftGr($purchaseOrder, $item, 10, 10, 0);
    putJson('/api/goods-receipts/' . $draftId, ['status' => 'confirmed'])->assertOk();

    $grItem = GoodsReceiptItem::query()->where('goods_receipt_id', $draftId)->firstOrFail();
    $goodsReceipt = GoodsReceipt::query()->findOrFail($draftId);

    postJson('/api/supplier-returns', [
        'goods_receipt_id' => $goodsReceipt->id,
        'purchase_order_id' => $goodsReceipt->purchase_order_id,
        'warehouse_id' => $goodsReceipt->warehouse_id,
        'supplier_id' => $purchaseOrder->supplier_id,
        'return_date' => now()->subDays(8)->toDateString(),
        'reason' => 'defective',
        'status' => 'confirmed',
        'items' => [[
            'goods_receipt_item_id' => $grItem->id,
            'product_id' => $grItem->product_id,
            'unit_id' => $grItem->unit_id,
            'quantity_returned' => 5,
            'unit_price' => 5000,
        ]],
    ])->assertStatus(422);

    expect(SupplierReturn::query()->where('goods_receipt_id', $goodsReceipt->id)->count())->toBe(0);
});

test('butir 2: stock transfer dispatch dated before a negative running balance is rejected with 422', function () {
    // Stok 10 masuk tiga hari lalu. Transfer kirim 5 bertanggal delapan hari lalu.
    [$warehouse, $product, $unit] = review3StockWithBalance(10, now()->subDays(3)->toDateString());
    $destination = Warehouse::factory()->create();

    $transfer = StockTransfer::factory()->create([
        'from_warehouse_id' => $warehouse->id,
        'to_warehouse_id' => $destination->id,
        'transfer_date' => now()->subDays(8)->toDateString(),
        'status' => 'draft',
    ]);
    StockTransferItem::factory()->create([
        'stock_transfer_id' => $transfer->id,
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'quantity' => 5,
    ]);

    putJson('/api/stock-transfers/' . $transfer->id, ['status' => 'in_transit'])
        ->assertStatus(422);

    expect($transfer->refresh()->status)->toBe('draft');
    expect(StockMovement::query()
        ->where('reference_type', StockTransfer::class)
        ->where('reference_id', $transfer->id)
        ->count())->toBe(0);
});

test('butir 2: stock transfer dispatch within available running balance is accepted', function () {
    createInTransitAccountFixture();
    [$warehouse, $product, $unit] = review3StockWithBalance(10, now()->subDays(3)->toDateString());
    $destination = Warehouse::factory()->create();

    $transfer = StockTransfer::factory()->create([
        'from_warehouse_id' => $warehouse->id,
        'to_warehouse_id' => $destination->id,
        'transfer_date' => now()->subDays(1)->toDateString(),
        'status' => 'draft',
    ]);
    StockTransferItem::factory()->create([
        'stock_transfer_id' => $transfer->id,
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'quantity' => 5,
    ]);

    putJson('/api/stock-transfers/' . $transfer->id, ['status' => 'in_transit'])
        ->assertOk();

    expect($transfer->refresh()->status)->toBe('in_transit');
});
