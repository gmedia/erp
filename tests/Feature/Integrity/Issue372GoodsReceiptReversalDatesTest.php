<?php

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockPostingService;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('stock-posting-issue-372');

/**
 * GR confirmed bertanggal $receiptDate dengan satu item $quantity (diposting langsung).
 */
function issue372HttpConfirmedReceipt(
    Warehouse $warehouse,
    Product $product,
    float $quantity,
    CarbonInterface $receiptDate,
): GoodsReceipt {
    $purchaseOrder = PurchaseOrder::factory()->create(['warehouse_id' => $warehouse->id]);
    $receipt = GoodsReceipt::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
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
 * Penyesuaian stok approved (qty negatif) bertanggal $date sebagai pengeluaran.
 */
function issue372HttpOutflow(Warehouse $warehouse, Product $product, float $quantity, CarbonInterface $date): void
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

    app(StockPostingService::class)->postStockAdjustment($adjustment->refresh());
}

function issue372HttpBalanceOn(Product $product, Warehouse $warehouse, CarbonInterface $date): float
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
 * Payload PUT GR confirmed dengan satu item $quantity. Item wajib menunjuk baris PO
 * milik PO GR, seperti request nyata dari form.
 *
 * @return array<string, mixed>
 */
function issue372HttpEditPayload(GoodsReceipt $receipt, Product $product, float $quantity): array
{
    $purchaseOrder = PurchaseOrder::query()->findOrFail($receipt->purchase_order_id);
    $unit = Unit::factory()->create();
    $poItem = $purchaseOrder->items()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'quantity' => 10,
        'unit_price' => 5000,
        'discount_percent' => 0,
        'tax_percent' => 0,
        'line_total' => 50000,
    ]);

    return [
        'items' => [
            [
                'purchase_order_item_id' => $poItem->id,
                'product_id' => $product->id,
                'unit_id' => $unit->id,
                'quantity_received' => $quantity,
                'quantity_accepted' => $quantity,
                'quantity_rejected' => 0,
                'unit_price' => 5000,
            ],
        ],
    ];
}

beforeEach(function () {
    Sanctum::actingAs(
        createTestUserWithPermissions(['goods_receipt', 'goods_receipt.edit', 'goods_receipt.delete']),
        ['*'],
    );
});

describe('Issue #372 — HTTP GR: edit memakai moved_at movement asli', function () {
    test('edit GR confirmed 4 menjadi 3 lewat API: pembalik bertanggal tanggal GR, bukan hari edit', function () {
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $receiptDate = now()->subDays(10);

        $receipt = issue372HttpConfirmedReceipt($warehouse, $product, 4, $receiptDate);

        putJson('/api/goods-receipts/' . $receipt->id, issue372HttpEditPayload($receipt, $product, 3))
            ->assertOk();

        $reversal = StockMovement::query()
            ->where('movement_type', 'goods_receipt_reversal')
            ->where('reference_type', GoodsReceipt::class)
            ->where('reference_id', $receipt->id)
            ->latest('id')
            ->firstOrFail();

        expect($reversal->moved_at->toDateString())->toBe($receiptDate->toDateString());
        expect(issue372HttpBalanceOn($product, $warehouse, now()->subDays(5)))->toBe(3.0);
        expect(issue372HttpBalanceOn($product, $warehouse, now()))->toBe(3.0);
    });

    test('edit GR yang membuat saldo negatif setelah pengeluaran ditolak 422 tanpa pembalik', function () {
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $receipt = issue372HttpConfirmedReceipt($warehouse, $product, 4, now()->subDays(10));
        issue372HttpOutflow($warehouse, $product, 3, now()->subDays(5));

        putJson('/api/goods-receipts/' . $receipt->id, issue372HttpEditPayload($receipt, $product, 2))
            ->assertStatus(422);

        expect(StockMovement::query()->where('movement_type', 'goods_receipt_reversal')->count())->toBe(0);
        expect(issue372HttpBalanceOn($product, $warehouse, now()))->toBe(1.0);
    });
});

describe('Issue #372 — HTTP GR: batal memakai tanggal pembatalan', function () {
    test('batal GR confirmed lewat API: pembalik bertanggal hari ini, riwayat tetap', function () {
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $receiptDate = now()->subDays(10);

        $receipt = issue372HttpConfirmedReceipt($warehouse, $product, 4, $receiptDate);

        putJson('/api/goods-receipts/' . $receipt->id, ['status' => 'cancelled'])->assertOk();

        $reversal = StockMovement::query()
            ->where('movement_type', 'goods_receipt_reversal')
            ->where('reference_type', GoodsReceipt::class)
            ->where('reference_id', $receipt->id)
            ->latest('id')
            ->firstOrFail();

        expect($reversal->moved_at->toDateString())->toBe(now()->toDateString());
        expect(issue372HttpBalanceOn($product, $warehouse, now()->subDays(5)))->toBe(4.0);
        expect(issue372HttpBalanceOn($product, $warehouse, now()))->toBe(0.0);
    });
});
