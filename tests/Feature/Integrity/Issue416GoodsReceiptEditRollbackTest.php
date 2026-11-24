<?php

use App\Models\Account;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('goods-receipts-issue-416');

/**
 * Issue #416 (dengan #372): edit GR confirmed yang membuat saldo negatif ditolak 422 lewat
 * PUT, dan tidak ada yang berubah: item GR, stok (movement), jurnal, maupun received qty PO.
 */
beforeEach(function () {
    $user = createTestUserWithPermissions([
        'goods_receipt', 'goods_receipt.create', 'goods_receipt.edit', 'goods_receipt.delete',
    ]);
    actingAs($user);

    createAccountingControlFixture();
});

/**
 * GR confirmed 4 unit @ 5000 bertanggal $receiptDate, lewat jalur posting normal.
 */
function issue416ConfirmedReceipt(
    Warehouse $warehouse,
    Product $product,
    float $quantity,
    string $receiptDate,
): GoodsReceipt {
    $unit = Unit::factory()->create();
    $purchaseOrder = PurchaseOrder::factory()->create([
        'warehouse_id' => $warehouse->id,
        'status' => 'confirmed',
    ]);
    $purchaseItem = $purchaseOrder->items()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'quantity' => $quantity,
        'unit_price' => 5000,
        'discount_percent' => 0,
        'tax_percent' => 0,
        'line_total' => $quantity * 5000,
    ]);

    $goodsReceipt = GoodsReceipt::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'warehouse_id' => $warehouse->id,
        'receipt_date' => $receiptDate,
        'status' => 'draft',
        'confirmed_at' => null,
        'confirmed_by' => null,
    ]);

    putJson('/api/goods-receipts/' . $goodsReceipt->id, [
        'status' => 'confirmed',
        'items' => [[
            'purchase_order_item_id' => $purchaseItem->id,
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'quantity_received' => $quantity,
            'quantity_accepted' => $quantity,
            'quantity_rejected' => 0,
            'unit_price' => 5000,
        ]],
    ])->assertOk();

    return $goodsReceipt->refresh();
}

/**
 * Snapshot seluruh keadaan yang tidak boleh berubah saat edit ditolak.
 *
 * @return array<string, mixed>
 */
function issue416Snapshot(GoodsReceipt $goodsReceipt, Product $product, Warehouse $warehouse): array
{
    $purchaseItem = PurchaseOrderItem::query()
        ->where('purchase_order_id', $goodsReceipt->purchase_order_id)
        ->firstOrFail();

    return [
        'items' => GoodsReceiptItem::query()
            ->where('goods_receipt_id', $goodsReceipt->id)
            ->orderBy('id')
            ->get(['id', 'quantity_received', 'quantity_accepted', 'unit_price'])
            ->map(fn ($item) => $item->toArray())
            ->all(),
        'receipt' => [
            'status' => $goodsReceipt->fresh()->status,
            'journal_entry_id' => $goodsReceipt->fresh()->journal_entry_id,
            'confirmed_at' => $goodsReceipt->fresh()->confirmed_at?->toIso8601String(),
        ],
        'movement_count' => StockMovement::query()
            ->where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->count(),
        'balance' => (float) StockMovement::query()
            ->where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->orderByDesc('moved_at')
            ->orderByDesc('id')
            ->value('balance_after'),
        'journal_lines' => JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entries.source_type', GoodsReceipt::class)
            ->where('journal_entries.source_id', $goodsReceipt->id)
            ->count(),
        'journal_entries' => JournalEntry::query()
            ->where('source_type', GoodsReceipt::class)
            ->where('source_id', $goodsReceipt->id)
            ->count(),
        'po_received' => (string) $purchaseItem->fresh()->quantity_received,
    ];
}

test('konfirmasi GR tanpa COA lengkap ditolak 422 dan dokumen, stok, jurnal, PO tidak berubah', function () {
    // Issue #416 inti: jurnal gagal (akun 11300/21150 hilang) harus membatalkan seluruh
    // transaksi. Sebelum perbaikan, penangkapan di afterCommit menyimpan dokumen dan stok.
    Account::query()->where('code', '11300')->delete();

    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create();
    $unit = Unit::factory()->create();
    $purchaseOrder = PurchaseOrder::factory()->create([
        'warehouse_id' => $warehouse->id,
        'status' => 'confirmed',
    ]);
    $purchaseItem = $purchaseOrder->items()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'quantity' => 4,
        'unit_price' => 5000,
        'discount_percent' => 0,
        'tax_percent' => 0,
        'line_total' => 20000,
    ]);
    $goodsReceipt = GoodsReceipt::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'warehouse_id' => $warehouse->id,
        'receipt_date' => now()->toDateString(),
        'status' => 'draft',
        'confirmed_at' => null,
        'confirmed_by' => null,
    ]);

    $before = issue416Snapshot($goodsReceipt, $product, $warehouse);

    putJson('/api/goods-receipts/' . $goodsReceipt->id, [
        'status' => 'confirmed',
        'items' => [[
            'purchase_order_item_id' => $purchaseItem->id,
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'quantity_received' => 4,
            'quantity_accepted' => 4,
            'quantity_rejected' => 0,
            'unit_price' => 5000,
        ]],
    ])->assertStatus(422);

    expect(issue416Snapshot($goodsReceipt, $product, $warehouse))->toBe($before);
    expect($goodsReceipt->refresh()->status)->toBe('draft');
});

test('edit GR confirmed yang membuat saldo negatif ditolak 422 dan tidak mengubah apa pun', function () {
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create();

    // GR 4 (10 hari lalu), keluar 3 (5 hari lalu). Edit GR menjadi 2 membuat saldo
    // pada tanggal keluar = -1, sehingga harus ditolak (#372).
    $goodsReceipt = issue416ConfirmedReceipt($warehouse, $product, 4, now()->subDays(10)->toDateString());

    /** @var StockAdjustment $adjustment */
    $adjustment = StockAdjustment::factory()->create([
        'warehouse_id' => $warehouse->id,
        'adjustment_date' => now()->subDays(5)->toDateString(),
        'status' => 'approved',
        'inventory_stocktake_id' => null,
        'journal_entry_id' => null,
        'approved_by' => User::factory()->create()->id,
        'approved_at' => now(),
    ]);
    StockAdjustmentItem::factory()->create([
        'stock_adjustment_id' => $adjustment->id,
        'product_id' => $product->id,
        'quantity_adjusted' => -3,
    ]);
    app(StockPostingService::class)->postStockAdjustment($adjustment->refresh());

    $before = issue416Snapshot($goodsReceipt, $product, $warehouse);
    $itemId = $goodsReceipt->items()->value('id');

    putJson('/api/goods-receipts/' . $goodsReceipt->id, [
        'status' => 'confirmed',
        'items' => [[
            'id' => $itemId,
            'purchase_order_item_id' => $goodsReceipt->items()->value('purchase_order_item_id'),
            'product_id' => $product->id,
            'unit_id' => $goodsReceipt->items()->value('unit_id'),
            'quantity_received' => 2,
            'quantity_accepted' => 2,
            'quantity_rejected' => 0,
            'unit_price' => 5000,
        ]],
    ])->assertStatus(422);

    expect(issue416Snapshot($goodsReceipt, $product, $warehouse))->toBe($before);
});
