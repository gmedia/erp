<?php

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('purchase-order-status-report');

beforeEach(function () {
    $this->testUser = createTestUserWithPermissions(['purchase_order_status_report']);
    $this->otherUserAccount = createTestUserWithPermissions([]);
    Sanctum::actingAs($this->testUser, ['*']);
});

test('it requires permission to access purchase order status report', function () {
    actingAs($this->otherUserAccount)
        ->get('/api/reports/purchase-order-status')
        ->assertForbidden();
});

test('it can render purchase order status report page', function () {
    PurchaseOrder::factory()->create();

    actingAs($this->testUser)
        ->get('/api/reports/purchase-order-status')
        ->assertOk();
});

test('it can fetch aggregated purchase order status report data', function () {
    $supplier = Supplier::factory()->create(['name' => 'PT Supplier Maju']);
    $warehouse = Warehouse::factory()->create(['name' => 'Gudang Utama', 'code' => 'WH-UTM']);
    $product = Product::factory()->create(['name' => 'Produk PO', 'code' => 'PO-001']);
    $unit = Unit::factory()->create(['name' => 'PCS']);

    $poOutstanding = PurchaseOrder::factory()->create([
        'po_number' => 'PO-OUT-001',
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'order_date' => '2026-03-01',
        'status' => 'confirmed',
        'grand_total' => 1000000,
    ]);

    PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $poOutstanding->id,
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'quantity' => 10,
        'quantity_received' => 0,
    ]);

    $poPartial = PurchaseOrder::factory()->create([
        'po_number' => 'PO-PAR-001',
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'order_date' => '2026-03-02',
        'status' => 'partially_received',
        'grand_total' => 1500000,
    ]);

    PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $poPartial->id,
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'quantity' => 10,
        'quantity_received' => 4,
    ]);

    $poClosed = PurchaseOrder::factory()->create([
        'po_number' => 'PO-CLS-001',
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'order_date' => '2026-03-03',
        'status' => 'closed',
        'grand_total' => 2000000,
    ]);

    PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $poClosed->id,
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'quantity' => 8,
        'quantity_received' => 8,
    ]);

    $response = actingAs($this->testUser)
        ->getJson('/api/reports/purchase-order-status')
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data', 3)
            ->has('meta')
            ->has('links')
        );

    $rows = collect($response->json('data'));

    expect($rows->firstWhere('purchase_order.po_number', 'PO-OUT-001')['purchase_order']['status_category'])->toBe('outstanding');
    expect($rows->firstWhere('purchase_order.po_number', 'PO-PAR-001')['purchase_order']['status_category'])->toBe('partially_received');
    expect($rows->firstWhere('purchase_order.po_number', 'PO-CLS-001')['purchase_order']['status_category'])->toBe('closed');
});

test('it can filter by supplier, product, status category and date range', function () {
    $supplierA = Supplier::factory()->create(['name' => 'Supplier A']);
    $supplierB = Supplier::factory()->create(['name' => 'Supplier B']);
    $warehouse = Warehouse::factory()->create();
    $productA = Product::factory()->create(['name' => 'Produk A']);
    $productB = Product::factory()->create(['name' => 'Produk B']);
    $unit = Unit::factory()->create();

    $poA = PurchaseOrder::factory()->create([
        'po_number' => 'PO-FLT-001',
        'supplier_id' => $supplierA->id,
        'warehouse_id' => $warehouse->id,
        'order_date' => '2026-03-01',
        'status' => 'confirmed',
    ]);

    PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $poA->id,
        'product_id' => $productA->id,
        'unit_id' => $unit->id,
        'quantity' => 12,
        'quantity_received' => 0,
    ]);

    $poB = PurchaseOrder::factory()->create([
        'po_number' => 'PO-FLT-002',
        'supplier_id' => $supplierB->id,
        'warehouse_id' => $warehouse->id,
        'order_date' => '2026-03-10',
        'status' => 'partially_received',
    ]);

    PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $poB->id,
        'product_id' => $productB->id,
        'unit_id' => $unit->id,
        'quantity' => 12,
        'quantity_received' => 4,
    ]);

    actingAs($this->testUser)
        ->getJson('/api/reports/purchase-order-status?supplier_id=' . $supplierA->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.supplier.id', $supplierA->id);

    actingAs($this->testUser)
        ->getJson('/api/reports/purchase-order-status?product_id=' . $productA->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.purchase_order.po_number', 'PO-FLT-001');

    actingAs($this->testUser)
        ->getJson('/api/reports/purchase-order-status?status_category=partially_received')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.purchase_order.po_number', 'PO-FLT-002');

    actingAs($this->testUser)
        ->getJson('/api/reports/purchase-order-status?start_date=2026-03-05&end_date=2026-03-31')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.purchase_order.po_number', 'PO-FLT-002');
});

test('it can export purchase order status report', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-04 10:00:00'));
    Excel::fake();
    Storage::fake('public');

    $response = actingAs($this->testUser)
        ->postJson('/api/reports/purchase-order-status/export')
        ->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');
    expect($filename)->toStartWith('purchase_order_status_report_2026-03-04_10-00-00_');
    expect($filename)->toEndWith('.xlsx');

    Excel::assertStored('exports/' . $filename, 'public');
    Carbon::setTestNow();
});
