<?php

use App\Actions\AccountingPosting\PostGoodsReceiptJournalAction;
use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\GoodsReceipt;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('goods-receipts');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'goods_receipt',
        'goods_receipt.create',
    ]);
    Sanctum::actingAs($user, ['*']);
});

/**
 * Fixture COA aktif per tanggal (sesuai #357) untuk jurnal GR: persediaan 11300 dan GR/IR 21150.
 * Refs #367: POST GR berstatus confirmed harus membentuk stok dan jurnal dalam satu request.
 */
function makeGrPostOnCreateFixture(): array
{
    FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);
    $coaVersion = CoaVersion::factory()->create(['status' => 'active']);

    Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '11300',
        'name' => 'Persediaan',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);
    Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '21150',
        'name' => 'Barang Diterima Belum Ditagih',
        'type' => 'liability',
        'normal_balance' => 'credit',
        'is_active' => true,
    ]);

    $purchaseOrder = PurchaseOrder::factory()->create(['status' => 'confirmed']);
    /** @var PurchaseOrderItem $purchaseOrderItem */
    $purchaseOrderItem = $purchaseOrder->items()->create([
        'product_id' => Product::factory()->create()->id,
        'unit_id' => Unit::factory()->create()->id,
        'quantity' => 10,
        'unit_price' => 10000,
        'discount_percent' => 0,
        'tax_percent' => 0,
        'line_total' => 100000,
    ]);

    return [$purchaseOrder, $purchaseOrderItem];
}

function grPostOnCreatePayload(
    PurchaseOrder $purchaseOrder,
    PurchaseOrderItem $purchaseOrderItem,
    string $deliveryNote,
): array {
    return [
        'purchase_order_id' => $purchaseOrder->id,
        'warehouse_id' => $purchaseOrder->warehouse_id,
        'receipt_date' => '2026-03-06',
        'supplier_delivery_note' => $deliveryNote,
        'status' => 'confirmed',
        'items' => [
            [
                'purchase_order_item_id' => $purchaseOrderItem->id,
                'product_id' => $purchaseOrderItem->product_id,
                'unit_id' => $purchaseOrderItem->unit_id,
                'quantity_received' => 6,
                'quantity_accepted' => 6,
                'quantity_rejected' => 0,
                'unit_price' => 10000,
            ],
        ],
    ];
}

test('POST goods receipt with status confirmed creates stock movement and posted journal (Refs #367)', function () {
    [$purchaseOrder, $purchaseOrderItem] = makeGrPostOnCreateFixture();

    $response = postJson('/api/goods-receipts', grPostOnCreatePayload($purchaseOrder, $purchaseOrderItem, 'SJ-367-OK'));

    $response->assertCreated();

    $goodsReceipt = GoodsReceipt::query()->findOrFail($response->json('data.id'));

    assertDatabaseHas('stock_movements', [
        'movement_type' => 'goods_receipt',
        'reference_type' => GoodsReceipt::class,
        'reference_id' => $goodsReceipt->id,
        'product_id' => $purchaseOrderItem->product_id,
        'quantity_in' => 6,
    ]);

    expect($goodsReceipt->journal_entry_id)->not->toBeNull();
    expect(JournalEntry::query()->findOrFail($goodsReceipt->journal_entry_id)->status)->toBe('posted');
});

test('POST goods receipt with status confirmed rolls back when journal posting fails (Refs #367)', function () {
    [$purchaseOrder, $purchaseOrderItem] = makeGrPostOnCreateFixture();

    $this->app->bind(PostGoodsReceiptJournalAction::class, function () {
        return new class
        {
            public function execute(GoodsReceipt $goodsReceipt): void
            {
                throw new RuntimeException('Simulated journal posting failure');
            }
        };
    });

    postJson('/api/goods-receipts', grPostOnCreatePayload($purchaseOrder, $purchaseOrderItem, 'SJ-367-FAIL'))
        ->assertStatus(500);

    assertDatabaseMissing('goods_receipts', ['supplier_delivery_note' => 'SJ-367-FAIL']);
    assertDatabaseMissing('stock_movements', [
        'movement_type' => 'goods_receipt',
        'product_id' => $purchaseOrderItem->product_id,
    ]);
});
