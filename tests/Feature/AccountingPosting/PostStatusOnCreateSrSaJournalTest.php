<?php

use App\Actions\AccountingPosting\PostStockAdjustmentJournalAction;
use App\Actions\AccountingPosting\PostSupplierReturnJournalAction;
use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\GoodsReceipt;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\SupplierReturn;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('supplier-returns', 'stock-adjustments');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'supplier_return',
        'supplier_return.create',
        'stock_adjustment',
        'stock_adjustment.create',
    ]);
    Sanctum::actingAs($user, ['*']);
});

/**
 * Fixture COA aktif per tanggal (sesuai #357) untuk jurnal SR (persediaan 11300, GR/IR 21150)
 * dan SA (persediaan 11300, beban penyesuaian stok 51000). Refs #367.
 */
function makeSrSaPostOnCreateFixture(): array
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
        'name' => 'Inventory',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);
    Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '21150',
        'name' => 'Goods Received Not Invoiced (GR/IR)',
        'type' => 'liability',
        'normal_balance' => 'credit',
        'is_active' => true,
    ]);
    Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '51000',
        'name' => 'Cost of Goods Sold',
        'type' => 'expense',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);

    $supplier = Supplier::factory()->create();
    $warehouse = Warehouse::factory()->create();
    $purchaseOrder = PurchaseOrder::factory()->create([
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
    ]);
    $product = Product::factory()->create();
    $unit = Unit::factory()->create();

    $purchaseOrderItem = $purchaseOrder->items()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'quantity' => 10,
        'unit_price' => 5000,
        'discount_percent' => 0,
        'tax_percent' => 0,
        'line_total' => 50000,
    ]);

    $goodsReceipt = GoodsReceipt::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'warehouse_id' => $warehouse->id,
    ]);
    $goodsReceiptItem = $goodsReceipt->items()->create([
        'purchase_order_item_id' => $purchaseOrderItem->id,
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'quantity_received' => 5,
        'quantity_accepted' => 5,
        'quantity_rejected' => 0,
        'unit_price' => 5000,
    ]);

    // Stok masuk sah agar retur confirmed tidak membuat saldo negatif (#356).
    StockMovement::factory()->create([
        'movement_type' => 'goods_receipt',
        'reference_type' => GoodsReceipt::class,
        'reference_id' => $goodsReceipt->id,
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'quantity_in' => 5,
        'quantity_out' => 0,
        'balance_after' => 5,
        'moved_at' => now()->subDay(),
    ]);

    return [
        'purchase_order' => $purchaseOrder,
        'goods_receipt' => $goodsReceipt,
        'goods_receipt_item' => $goodsReceiptItem,
        'supplier' => $supplier,
        'warehouse' => $warehouse,
        'product' => $product,
        'unit' => $unit,
    ];
}

function srPostOnCreatePayload(array $fixture, string $returnNumber): array
{
    return [
        'return_number' => $returnNumber,
        'purchase_order_id' => $fixture['purchase_order']->id,
        'goods_receipt_id' => $fixture['goods_receipt']->id,
        'supplier_id' => $fixture['supplier']->id,
        'warehouse_id' => $fixture['warehouse']->id,
        'return_date' => '2026-03-06',
        'reason' => 'defective',
        'status' => 'confirmed',
        'items' => [
            [
                'goods_receipt_item_id' => $fixture['goods_receipt_item']->id,
                'product_id' => $fixture['product']->id,
                'unit_id' => $fixture['unit']->id,
                'quantity_returned' => 2,
                'unit_price' => 5000,
            ],
        ],
    ];
}

function saPostOnCreatePayload(array $fixture, string $adjustmentNumber): array
{
    return [
        'adjustment_number' => $adjustmentNumber,
        'warehouse_id' => $fixture['warehouse']->id,
        'adjustment_date' => '2026-03-06',
        'adjustment_type' => 'damage',
        'status' => 'approved',
        'items' => [
            [
                'product_id' => $fixture['product']->id,
                'unit_id' => $fixture['unit']->id,
                'quantity_before' => 5,
                'quantity_adjusted' => -1,
                'unit_cost' => 5000,
                'reason' => 'Rusak',
            ],
        ],
    ];
}

test('POST supplier return confirmed posts journal and stock movement in one request (Refs #367)', function () {
    $fixture = makeSrSaPostOnCreateFixture();

    $response = postJson('/api/supplier-returns', srPostOnCreatePayload($fixture, 'SR-2026-367001'));

    $response->assertCreated();

    $supplierReturn = SupplierReturn::query()->findOrFail($response->json('data.id'));

    expect($supplierReturn->journal_entry_id)->not->toBeNull();
    expect(JournalEntry::query()->findOrFail($supplierReturn->journal_entry_id)->status)->toBe('posted');
    assertDatabaseHas('stock_movements', [
        'movement_type' => 'supplier_return',
        'reference_type' => SupplierReturn::class,
        'reference_id' => $supplierReturn->id,
    ]);
});

test('POST supplier return with status confirmed rolls back when journal posting fails (Refs #367)', function () {
    $fixture = makeSrSaPostOnCreateFixture();

    $this->app->bind(PostSupplierReturnJournalAction::class, function () {
        return new class
        {
            public function execute(SupplierReturn $supplierReturn): void
            {
                throw new RuntimeException('Simulated journal posting failure');
            }
        };
    });

    postJson('/api/supplier-returns', srPostOnCreatePayload($fixture, 'SR-2026-367002'))
        ->assertStatus(500);

    assertDatabaseMissing('supplier_returns', ['return_number' => 'SR-2026-367002']);
});

test('POST stock adjustment with status approved posts journal in the same request (Refs #367)', function () {
    $fixture = makeSrSaPostOnCreateFixture();

    $response = postJson('/api/stock-adjustments', saPostOnCreatePayload($fixture, 'SA-2026-367001'));

    $response->assertCreated();

    $adjustment = StockAdjustment::query()->findOrFail($response->json('data.id'));

    expect($adjustment->journal_entry_id)->not->toBeNull();
    expect(JournalEntry::query()->findOrFail($adjustment->journal_entry_id)->status)->toBe('posted');
});

test('POST stock adjustment with status approved rolls back when journal posting fails (Refs #367)', function () {
    $fixture = makeSrSaPostOnCreateFixture();

    $this->app->bind(PostStockAdjustmentJournalAction::class, function () {
        return new class
        {
            public function execute(StockAdjustment $stockAdjustment): void
            {
                throw new RuntimeException('Simulated journal posting failure');
            }
        };
    });

    postJson('/api/stock-adjustments', saPostOnCreatePayload($fixture, 'SA-2026-367002'))
        ->assertStatus(500);

    assertDatabaseMissing('stock_adjustments', ['adjustment_number' => 'SA-2026-367002']);
});
