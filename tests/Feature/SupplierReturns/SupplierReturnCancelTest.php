<?php

use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\GoodsReceipt;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\SupplierReturn;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('supplier-returns');

/**
 * Membuat retur confirmed lengkap dengan stok dan jurnal asli (via API confirm),
 * supaya pembatalan diuji terhadap jejak yang sama dengan produksi.
 *
 * @return array{0: SupplierReturn, 1: Warehouse, 2: Product}
 */
function confirmedSupplierReturnForCancel(): array
{
    $year = now()->format('Y');
    FiscalYear::factory()->create([
        'name' => $year,
        'start_date' => $year . '-01-01',
        'end_date' => $year . '-12-31',
        'status' => 'open',
    ]);

    $coaVersion = CoaVersion::factory()->create(['status' => 'active']);
    foreach ([['21100', 'liability', 'credit'], ['11300', 'asset', 'debit']] as [$code, $type, $normal]) {
        Account::factory()->create([
            'coa_version_id' => $coaVersion->id,
            'code' => $code,
            'type' => $type,
            'normal_balance' => $normal,
            'is_active' => true,
        ]);
    }

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

    // Stok masuk sah dari penerimaan barang (5 unit) sebelum retur 3 unit dikonfirmasi.
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

    $supplierReturn = SupplierReturn::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'goods_receipt_id' => $goodsReceipt->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'return_date' => now()->format('Y-m-d'),
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
                'unit_price' => 5000,
            ],
        ],
    ])->assertOk();

    return [$supplierReturn->refresh(), $warehouse, $product];
}

function stockBalanceFor(int $productId, int $warehouseId): float
{
    return (float) StockMovement::query()
        ->where('product_id', $productId)
        ->where('warehouse_id', $warehouseId)
        ->orderByDesc('moved_at')
        ->orderByDesc('id')
        ->value('balance_after');
}

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'supplier_return',
        'supplier_return.create',
        'supplier_return.edit',
        'supplier_return.delete',
    ]);
    Sanctum::actingAs($user, ['*']);
});

test('cancelling a confirmed supplier return reverses stock with a reversal movement and keeps history', function () {
    [$supplierReturn, $warehouse, $product] = confirmedSupplierReturnForCancel();

    // Stok 5 - retur confirmed 3 = saldo 2.
    expect(stockBalanceFor($product->id, $warehouse->id))->toBe(2.0);

    putJson('/api/supplier-returns/' . $supplierReturn->id, ['status' => 'cancelled'])
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');

    // Pembalik mengembalikan 3 unit: saldo kembali ke 5, histori movement tidak dihapus.
    expect(stockBalanceFor($product->id, $warehouse->id))->toBe(5.0);

    assertDatabaseHas('stock_movements', [
        'movement_type' => 'supplier_return',
        'reference_type' => SupplierReturn::class,
        'reference_id' => $supplierReturn->id,
        'product_id' => $product->id,
        'quantity_out' => 3,
    ]);
    assertDatabaseHas('stock_movements', [
        'movement_type' => 'supplier_return_reversal',
        'reference_type' => SupplierReturn::class,
        'reference_id' => $supplierReturn->id,
        'product_id' => $product->id,
        'quantity_in' => 3,
    ]);
});

test('cancelling a confirmed supplier return posts a reversing journal that mirrors the original lines', function () {
    [$supplierReturn] = confirmedSupplierReturnForCancel();

    $original = JournalEntry::query()->findOrFail($supplierReturn->journal_entry_id);
    $originalLines = JournalEntryLine::query()->where('journal_entry_id', $original->id)->get();

    putJson('/api/supplier-returns/' . $supplierReturn->id, ['status' => 'cancelled'])->assertOk();

    $reversal = JournalEntry::query()
        ->where('source_type', SupplierReturn::class)
        ->where('source_id', $supplierReturn->id)
        ->where('id', '!=', $original->id)
        ->firstOrFail();

    expect($reversal->status)->toBe('posted');
    expect($reversal->entry_date->format('Y-m-d'))->toBe(now()->format('Y-m-d'));

    $reversalLines = JournalEntryLine::query()->where('journal_entry_id', $reversal->id)->get();
    expect($reversalLines)->toHaveCount($originalLines->count());

    foreach ($originalLines as $line) {
        $mirror = $reversalLines->firstWhere('account_id', $line->account_id);
        expect($mirror)->not->toBeNull();
        expect((float) $mirror->debit)->toBe((float) $line->credit);
        expect((float) $mirror->credit)->toBe((float) $line->debit);
    }
});

test('cancelling with a closed fiscal year is rejected with 422 and keeps stock', function () {
    [$supplierReturn, $warehouse, $product] = confirmedSupplierReturnForCancel();

    FiscalYear::query()
        ->where('start_date', '<=', now()->format('Y-m-d'))
        ->where('end_date', '>=', now()->format('Y-m-d'))
        ->update(['status' => 'closed']);

    $balanceBefore = stockBalanceFor($product->id, $warehouse->id);
    $movementsBefore = StockMovement::query()->count();

    putJson('/api/supplier-returns/' . $supplierReturn->id, ['status' => 'cancelled'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['entry_date']);

    $supplierReturn->refresh();
    expect($supplierReturn->status)->toBe('confirmed');
    expect(stockBalanceFor($product->id, $warehouse->id))->toBe($balanceBefore);
    expect(StockMovement::query()->count())->toBe($movementsBefore);
});

test('cancelling an already cancelled supplier return is rejected and does not reverse twice', function () {
    [$supplierReturn, $warehouse, $product] = confirmedSupplierReturnForCancel();

    putJson('/api/supplier-returns/' . $supplierReturn->id, ['status' => 'cancelled'])->assertOk();

    $balanceAfterFirst = stockBalanceFor($product->id, $warehouse->id);
    $reversalMovements = StockMovement::query()->where('movement_type', 'supplier_return_reversal')->count();
    $reversalEntries = JournalEntry::query()
        ->where('source_type', SupplierReturn::class)
        ->where('source_id', $supplierReturn->id)
        ->count();

    putJson('/api/supplier-returns/' . $supplierReturn->id, ['status' => 'cancelled'])
        ->assertStatus(422);

    expect(stockBalanceFor($product->id, $warehouse->id))->toBe($balanceAfterFirst);
    expect(StockMovement::query()
        ->where('movement_type', 'supplier_return_reversal')
        ->count())->toBe($reversalMovements);
    expect(JournalEntry::query()
        ->where('source_type', SupplierReturn::class)
        ->where('source_id', $supplierReturn->id)
        ->count())->toBe($reversalEntries);
});

test('cancelling a confirmed supplier return stores the reason and who cancelled it and when', function () {
    [$supplierReturn] = confirmedSupplierReturnForCancel();

    putJson('/api/supplier-returns/' . $supplierReturn->id, [
        'status' => 'cancelled',
        'notes' => 'Barang rusak saat diterima',
    ])->assertOk();

    $notes = $supplierReturn->refresh()->notes;
    expect($notes)->toContain('Barang rusak saat diterima');
    expect($notes)->toContain('[Cancelled by user ' . Auth::user()->name . ' at ');
    expect($notes)->not->toContain('[Cancelled by user #');
});

test('a cancelled supplier return cannot be re-opened to draft or confirmed', function () {
    [$supplierReturn] = confirmedSupplierReturnForCancel();

    putJson('/api/supplier-returns/' . $supplierReturn->id, ['status' => 'cancelled'])->assertOk();

    putJson('/api/supplier-returns/' . $supplierReturn->id, ['status' => 'confirmed'])
        ->assertStatus(422);
    putJson('/api/supplier-returns/' . $supplierReturn->id, ['status' => 'draft'])
        ->assertStatus(422);

    expect($supplierReturn->refresh()->status)->toBe('cancelled');
});

test('a confirmed supplier return cannot be moved back to draft', function () {
    [$supplierReturn, $warehouse, $product] = confirmedSupplierReturnForCancel();

    putJson('/api/supplier-returns/' . $supplierReturn->id, ['status' => 'draft'])
        ->assertStatus(422);

    expect($supplierReturn->refresh()->status)->toBe('confirmed');
    expect(stockBalanceFor($product->id, $warehouse->id))->toBe(2.0);
});

test('a confirmed supplier return cannot be deleted and keeps its stock and journal', function () {
    [$supplierReturn, $warehouse, $product] = confirmedSupplierReturnForCancel();

    $movementsBefore = StockMovement::query()->count();
    $entriesBefore = JournalEntry::query()->count();

    deleteJson('/api/supplier-returns/' . $supplierReturn->id)->assertStatus(422);

    expect(SupplierReturn::query()->whereKey($supplierReturn->id)->exists())->toBeTrue();
    expect(StockMovement::query()->count())->toBe($movementsBefore);
    expect(JournalEntry::query()->count())->toBe($entriesBefore);
});

test('confirming a supplier return that exceeds stock is rejected with 422', function () {
    $year = now()->format('Y');
    FiscalYear::factory()->create([
        'name' => $year,
        'start_date' => $year . '-01-01',
        'end_date' => $year . '-12-31',
        'status' => 'open',
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
    $supplierReturn = SupplierReturn::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'goods_receipt_id' => $goodsReceipt->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'return_date' => now()->format('Y-m-d'),
        'status' => 'draft',
    ]);

    // Tidak ada stok masuk di gudang ini: konfirmasi 3 unit harus ditolak (#356).
    putJson('/api/supplier-returns/' . $supplierReturn->id, [
        'status' => 'confirmed',
        'items' => [[
            'goods_receipt_item_id' => $goodsReceiptItem->id,
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'quantity_returned' => 3,
            'unit_price' => 5000,
        ]],
    ])->assertStatus(422);

    expect($supplierReturn->refresh()->status)->toBe('draft');
    expect(StockMovement::query()->where('product_id', $product->id)->count())->toBe(0);
});

test('editing items of a confirmed supplier return is rejected and keeps stock', function () {
    [$supplierReturn, $warehouse, $product] = confirmedSupplierReturnForCancel();

    $item = $supplierReturn->items()->firstOrFail();

    putJson('/api/supplier-returns/' . $supplierReturn->id, [
        'items' => [[
            'goods_receipt_item_id' => $item->goods_receipt_item_id,
            'product_id' => $product->id,
            'unit_id' => $item->unit_id,
            'quantity_returned' => 1,
            'unit_price' => 5000,
        ]],
    ])->assertStatus(422);

    expect($supplierReturn->items()->firstOrFail()->quantity_returned)->toEqual(3.0);
    expect(stockBalanceFor($product->id, $warehouse->id))->toBe(2.0);
});

test('cancelling a confirmed supplier return requires the supplier_return.edit permission', function () {
    [$supplierReturn, $warehouse, $product] = confirmedSupplierReturnForCancel();

    Sanctum::actingAs(createTestUserWithPermissions(['supplier_return']), ['*']);

    putJson('/api/supplier-returns/' . $supplierReturn->id, ['status' => 'cancelled'])
        ->assertForbidden();

    expect($supplierReturn->refresh()->status)->toBe('confirmed');
});
