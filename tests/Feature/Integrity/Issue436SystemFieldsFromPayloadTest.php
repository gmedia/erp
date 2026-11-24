<?php

use App\Models\Branch;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\StockAdjustment;
use App\Models\StockTransfer;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('issue-436-system-fields');

/**
 * Issue #436 butir 1: field sistem (approved_*, shipped_*, received_*, journal_entry_id)
 * tidak boleh berasal dari payload. Server yang mengisinya saat transisi status.
 */
beforeEach(function () {
    $user = createTestUserWithPermissions([
        'purchase_order', 'purchase_order.create', 'purchase_order.edit',
        'purchase_request', 'purchase_request.create', 'purchase_request.edit',
        'stock_adjustment', 'stock_adjustment.create', 'stock_adjustment.edit',
        'stock_transfer', 'stock_transfer.create', 'stock_transfer.edit',
    ]);
    Sanctum::actingAs($user, ['*']);
});

test('purchase order store ignores approved fields from payload', function () {
    $product = Product::factory()->create();
    $unit = Unit::factory()->create();
    $warehouse = Warehouse::factory()->create();
    $supplier = Supplier::factory()->create();

    $response = postJson('/api/purchase-orders', [
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'order_date' => now()->toDateString(),
        'status' => 'draft',
        'currency' => 'IDR',
        'approved_by' => null,
        'approved_at' => '2020-01-01 00:00:00',
        'items' => [[
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'quantity' => 2,
            'unit_price' => 1000,
            'discount_percent' => 0,
            'tax_percent' => 0,
        ]],
    ]);

    $response->assertCreated();
    expect(PurchaseOrder::query()->findOrFail($response->json('data.id'))->approved_at)->toBeNull();
});

test('purchase request store ignores approved fields from payload', function () {
    $product = Product::factory()->create();
    $unit = Unit::factory()->create();
    $branch = Branch::factory()->create();

    $response = postJson('/api/purchase-requests', [
        'branch_id' => $branch->id,
        'request_date' => now()->toDateString(),
        'status' => 'draft',
        'priority' => 'normal',
        'approved_by' => null,
        'approved_at' => '2020-01-01 00:00:00',
        'items' => [[
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'quantity' => 1,
        ]],
    ]);

    $response->assertCreated();
    expect(PurchaseRequest::query()->findOrFail($response->json('data.id'))->approved_at)->toBeNull();
});

test('stock adjustment store ignores journal and approval fields from payload', function () {
    $product = Product::factory()->create();
    $unit = Unit::factory()->create();
    $warehouse = Warehouse::factory()->create();

    $response = postJson('/api/stock-adjustments', [
        'warehouse_id' => $warehouse->id,
        'adjustment_date' => now()->toDateString(),
        'adjustment_type' => 'correction',
        'status' => 'draft',
        'journal_entry_id' => null,
        'approved_by' => null,
        'approved_at' => '2020-01-01 00:00:00',
        'items' => [[
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'quantity_before' => 5,
            'quantity_adjusted' => 2,
        ]],
    ]);

    $response->assertCreated();
    $adjustment = StockAdjustment::query()->findOrFail($response->json('data.id'));
    expect($adjustment->approved_at)->toBeNull();
    expect($adjustment->journal_entry_id)->toBeNull();
});

test('stock transfer update ignores shipped and received fields from payload', function () {
    $from = Warehouse::factory()->create();
    $to = Warehouse::factory()->create();
    $transfer = StockTransfer::factory()->create([
        'from_warehouse_id' => $from->id,
        'to_warehouse_id' => $to->id,
        'status' => 'draft',
        'shipped_at' => null,
        'received_at' => null,
    ]);

    putJson('/api/stock-transfers/' . $transfer->id, [
        'status' => 'draft',
        'shipped_at' => '2020-01-01 00:00:00',
        'received_at' => '2020-01-02 00:00:00',
        'shipped_by' => null,
        'received_by' => null,
    ])->assertOk();

    $transfer->refresh();
    expect($transfer->shipped_at)->toBeNull();
    expect($transfer->received_at)->toBeNull();
});
