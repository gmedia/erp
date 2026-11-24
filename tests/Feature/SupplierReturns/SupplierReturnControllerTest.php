<?php

use App\Actions\AccountingPosting\PostSupplierReturnJournalAction;
use App\Actions\AccountingPosting\ResolveControlAccountAction;
use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\GoodsReceipt;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\SupplierReturn;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('supplier-returns');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'supplier_return',
        'supplier_return.create',
        'supplier_return.edit',
        'supplier_return.delete',
    ]);
    Sanctum::actingAs($user, ['*']);
});

/**
 * COA aktif lengkap untuk jurnal retur (persediaan 11300, GR/IR 21150). Issue #416: retur
 * confirmed tanpa COA lengkap kini ditolak 422.
 */
function createSupplierReturnCoaFixture(): void
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
}

function createGoodsReceiptItemForSupplierReturn(): array
{
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
        'tax_percent' => 11,
        'line_total' => 55500,
    ]);

    // Retur hanya untuk GR confirmed (Issue #436 butir 4); factory memilih status acak.
    $goodsReceipt = GoodsReceipt::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'warehouse_id' => $warehouse->id,
        'status' => 'confirmed',
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

    // Stok masuk sah dari penerimaan barang, supaya retur tidak membuat saldo negatif (#356).
    StockMovement::factory()->create([
        'movement_type' => 'goods_receipt',
        'reference_type' => GoodsReceipt::class,
        'reference_id' => $goodsReceipt->id,
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'quantity_in' => 5,
        'quantity_out' => 0,
        'balance_after' => 5,
        // Sebelum seluruh tanggal retur di tes (2026-03-06 s.d. 2026-04-01): saldo berjalan
        // sejak tanggal retur harus tetap nonnegatif (review putaran 3 butir 2).
        'moved_at' => '2026-01-01 00:00:00',
    ]);

    return [$purchaseOrder, $goodsReceipt, $goodsReceiptItem, $supplier, $warehouse, $product, $unit];
}

test('index returns paginated supplier returns', function () {
    SupplierReturn::factory()->count(20)->create();

    $response = getJson('/api/supplier-returns?per_page=10');

    $response->assertOk()
        ->assertJsonStructure([
            'data',
            'meta' => ['total', 'per_page', 'current_page'],
        ]);

    expect($response->json('data'))->toHaveCount(10);
});

test('index supports search and filters', function () {
    [$purchaseOrder, $goodsReceipt, , $supplier, $warehouse] = createGoodsReceiptItemForSupplierReturn();

    SupplierReturn::factory()->create([
        'return_number' => 'SR-SEARCH-001',
        'purchase_order_id' => $purchaseOrder->id,
        'goods_receipt_id' => $goodsReceipt->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'status' => 'draft',
    ]);
    SupplierReturn::factory()->create();

    getJson('/api/supplier-returns?search=SR-SEARCH-001')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    getJson('/api/supplier-returns?supplier_id=' . $supplier->id)
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('store creates supplier return with items', function () {
    [
        $purchaseOrder,
        $goodsReceipt,
        $goodsReceiptItem,
        $supplier,
        $warehouse,
        $product,
        $unit,
    ] = createGoodsReceiptItemForSupplierReturn();

    $payload = [
        'purchase_order_id' => $purchaseOrder->id,
        'goods_receipt_id' => $goodsReceipt->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'return_date' => '2026-03-06',
        'reason' => 'defective',
        'status' => 'draft',
        'items' => [
            [
                'goods_receipt_item_id' => $goodsReceiptItem->id,
                'product_id' => $product->id,
                'unit_id' => $unit->id,
                'quantity_returned' => 2,
                'unit_price' => 5000,
            ],
        ],
    ];

    $response = postJson('/api/supplier-returns', $payload);

    $response->assertCreated()
        ->assertJsonPath('data.supplier.id', $supplier->id)
        ->assertJsonPath('data.items.0.product.id', $product->id);

    $id = $response->json('data.id');
    assertDatabaseHas('supplier_returns', ['id' => $id, 'supplier_id' => $supplier->id]);
    assertDatabaseHas('supplier_return_items', [
        'supplier_return_id' => $id,
        'goods_receipt_item_id' => $goodsReceiptItem->id,
    ]);
});

test('show returns supplier return detail', function () {
    [
        $purchaseOrder,
        $goodsReceipt,
        $goodsReceiptItem,
        $supplier,
        $warehouse,
        $product,
        $unit,
    ] = createGoodsReceiptItemForSupplierReturn();
    $supplierReturn = SupplierReturn::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'goods_receipt_id' => $goodsReceipt->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
    ]);
    $supplierReturn->items()->create([
        'goods_receipt_item_id' => $goodsReceiptItem->id,
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'quantity_returned' => 1,
        'unit_price' => 5000,
    ]);

    getJson('/api/supplier-returns/' . $supplierReturn->id)
        ->assertOk()
        ->assertJsonPath('data.id', $supplierReturn->id)
        ->assertJsonCount(1, 'data.items');
});

test('update modifies supplier return and items', function () {
    createSupplierReturnCoaFixture();
    [
        $purchaseOrder,
        $goodsReceipt,
        $goodsReceiptItem,
        $supplier,
        $warehouse,
        $product,
        $unit,
    ] = createGoodsReceiptItemForSupplierReturn();
    $supplierReturn = SupplierReturn::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'goods_receipt_id' => $goodsReceipt->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'return_date' => '2026-04-01',
        'status' => 'draft',
        'confirmed_at' => null,
        'confirmed_by' => null,
    ]);

    $payload = [
        'status' => 'confirmed',
        'items' => [
            [
                'goods_receipt_item_id' => $goodsReceiptItem->id,
                'product_id' => $product->id,
                'unit_id' => $unit->id,
                'quantity_returned' => 3,
                'unit_price' => 10000,
            ],
        ],
    ];

    putJson('/api/supplier-returns/' . $supplierReturn->id, $payload)
        ->assertOk()
        ->assertJsonPath('data.status', 'confirmed')
        ->assertJsonCount(1, 'data.items');
});

test('confirming a supplier return creates supplier return stock movements', function () {
    createSupplierReturnCoaFixture();
    [
        $purchaseOrder,
        $goodsReceipt,
        $goodsReceiptItem,
        $supplier,
        $warehouse,
        $product,
        $unit,
    ] = createGoodsReceiptItemForSupplierReturn();
    $supplierReturn = SupplierReturn::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'goods_receipt_id' => $goodsReceipt->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'return_date' => '2026-04-01',
        'status' => 'draft',
        'confirmed_at' => null,
        'confirmed_by' => null,
    ]);

    putJson('/api/supplier-returns/' . $supplierReturn->id, [
        'status' => 'confirmed',
        'items' => [
            [
                'goods_receipt_item_id' => $goodsReceiptItem->id,
                'product_id' => $product->id,
                'unit_id' => $unit->id,
                'quantity_returned' => 3,
                'unit_price' => 10000,
            ],
        ],
    ])->assertOk();

    assertDatabaseHas('stock_movements', [
        'movement_type' => 'supplier_return',
        'reference_type' => SupplierReturn::class,
        'reference_id' => $supplierReturn->id,
        'product_id' => $product->id,
        'quantity_out' => 3,
    ]);
});

test('confirming supplier return rolls back state when journal posting fails with non-validation error', function () {
    $supplierReturn = SupplierReturn::factory()->create([
        'status' => 'draft',
        'confirmed_at' => null,
        'confirmed_by' => null,
    ]);

    $this->app->bind(PostSupplierReturnJournalAction::class, function ($app) {
        $journal = $app->make(CreateJournalEntryAction::class);
        $control = $app->make(ResolveControlAccountAction::class);

        return new class($journal, $control) extends PostSupplierReturnJournalAction
        {
            public function execute(SupplierReturn $supplierReturn): ?JournalEntry
            {
                throw new RuntimeException('Simulated journal posting failure');
            }
        };
    });

    putJson('/api/supplier-returns/' . $supplierReturn->id, ['status' => 'confirmed'])
        ->assertStatus(500);

    $supplierReturn->refresh();
    expect($supplierReturn->status)->toBe('draft');
    expect($supplierReturn->confirmed_at)->toBeNull();
    expect($supplierReturn->confirmed_by)->toBeNull();
});

test('confirming supplier return with missing control account is rejected with 422 unchanged', function () {
    [
        $purchaseOrder,
        $goodsReceipt,
        $goodsReceiptItem,
        $supplier,
        $warehouse,
        $product,
        $unit,
    ] = createGoodsReceiptItemForSupplierReturn();
    $supplierReturn = SupplierReturn::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'goods_receipt_id' => $goodsReceipt->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'return_date' => '2026-04-01',
        'status' => 'draft',
        'confirmed_at' => null,
        'confirmed_by' => null,
    ]);

    // Tidak ada COA aktif sama sekali: akun inventory 11300 tidak bisa di-resolve.
    CoaVersion::query()->delete();

    putJson('/api/supplier-returns/' . $supplierReturn->id, [
        'status' => 'confirmed',
        'items' => [
            [
                'goods_receipt_item_id' => $goodsReceiptItem->id,
                'product_id' => $product->id,
                'unit_id' => $unit->id,
                'quantity_returned' => 3,
                'unit_price' => 10000,
            ],
        ],
    ])->assertStatus(422);

    $supplierReturn->refresh();
    expect($supplierReturn->status)->toBe('draft')
        ->and($supplierReturn->confirmed_at)->toBeNull()
        ->and($supplierReturn->confirmed_by)->toBeNull()
        ->and($supplierReturn->journal_entry_id)->toBeNull()
        ->and($supplierReturn->items()->count())->toBe(0);

    assertDatabaseMissing('stock_movements', [
        'movement_type' => 'supplier_return',
        'reference_type' => SupplierReturn::class,
        'reference_id' => $supplierReturn->id,
    ]);
    assertDatabaseMissing('journal_entries', [
        'source_type' => SupplierReturn::class,
        'source_id' => $supplierReturn->id,
    ]);
});

test('confirming supplier return in a closed fiscal period is rejected with 422 unchanged', function () {
    createSupplierReturnCoaFixture();
    FiscalYear::query()->update(['status' => 'closed']);
    [
        $purchaseOrder,
        $goodsReceipt,
        $goodsReceiptItem,
        $supplier,
        $warehouse,
        $product,
        $unit,
    ] = createGoodsReceiptItemForSupplierReturn();
    $supplierReturn = SupplierReturn::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'goods_receipt_id' => $goodsReceipt->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'return_date' => '2026-04-01',
        'status' => 'draft',
        'confirmed_at' => null,
        'confirmed_by' => null,
    ]);

    putJson('/api/supplier-returns/' . $supplierReturn->id, [
        'status' => 'confirmed',
        'items' => [
            [
                'goods_receipt_item_id' => $goodsReceiptItem->id,
                'product_id' => $product->id,
                'unit_id' => $unit->id,
                'quantity_returned' => 3,
                'unit_price' => 10000,
            ],
        ],
    ])->assertStatus(422);

    $supplierReturn->refresh();
    expect($supplierReturn->status)->toBe('draft')
        ->and($supplierReturn->confirmed_at)->toBeNull()
        ->and($supplierReturn->journal_entry_id)->toBeNull();

    assertDatabaseMissing('stock_movements', [
        'movement_type' => 'supplier_return',
        'reference_type' => SupplierReturn::class,
        'reference_id' => $supplierReturn->id,
    ]);
});

test('confirming supplier return with ambiguous COA versions is rejected with 422 unchanged', function () {
    // Dua COA aktif dan tidak ada tahun fiskal yang mencakup tanggal retur: tidak boleh menebak.
    // Tahun fiskal eksplisit (bukan acak) agar tidak ada yang mencakup 2026-04-01.
    foreach ([2020, 2021] as $year) {
        CoaVersion::factory()->create([
            'status' => 'active',
            'fiscal_year_id' => FiscalYear::factory()->create([
                'start_date' => $year . '-01-01',
                'end_date' => $year . '-12-31',
            ])->id,
        ]);
    }
    [
        $purchaseOrder,
        $goodsReceipt,
        $goodsReceiptItem,
        $supplier,
        $warehouse,
        $product,
        $unit,
    ] = createGoodsReceiptItemForSupplierReturn();
    $supplierReturn = SupplierReturn::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'goods_receipt_id' => $goodsReceipt->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'return_date' => '2026-04-01',
        'status' => 'draft',
        'confirmed_at' => null,
        'confirmed_by' => null,
    ]);

    putJson('/api/supplier-returns/' . $supplierReturn->id, [
        'status' => 'confirmed',
        'items' => [
            [
                'goods_receipt_item_id' => $goodsReceiptItem->id,
                'product_id' => $product->id,
                'unit_id' => $unit->id,
                'quantity_returned' => 3,
                'unit_price' => 10000,
            ],
        ],
    ])->assertStatus(422)->assertJsonValidationErrors(['coa_version']);

    $supplierReturn->refresh();
    expect($supplierReturn->status)->toBe('draft')
        ->and($supplierReturn->confirmed_at)->toBeNull()
        ->and($supplierReturn->journal_entry_id)->toBeNull();

    assertDatabaseMissing('stock_movements', [
        'movement_type' => 'supplier_return',
        'reference_type' => SupplierReturn::class,
        'reference_id' => $supplierReturn->id,
    ]);
});

test('destroy removes a draft supplier return', function () {
    $supplierReturn = SupplierReturn::factory()->create(['status' => 'draft']);

    deleteJson('/api/supplier-returns/' . $supplierReturn->id)
        ->assertNoContent();

    assertDatabaseMissing('supplier_returns', ['id' => $supplierReturn->id]);
});

test('destroy rejects a confirmed supplier return', function () {
    $supplierReturn = SupplierReturn::factory()->create(['status' => 'confirmed']);

    deleteJson('/api/supplier-returns/' . $supplierReturn->id)
        ->assertStatus(422);

    assertDatabaseHas('supplier_returns', ['id' => $supplierReturn->id]);
});
