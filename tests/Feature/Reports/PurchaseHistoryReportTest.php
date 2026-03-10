<?php

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class)->group('purchase-history-report');

beforeEach(function () {
    $this->testUser = createTestUserWithPermissions(['purchase_history_report']);
    $this->otherUserAccount = createTestUserWithPermissions([]);
    Sanctum::actingAs($this->testUser, ['*']);
});

test('it requires permission to access purchase history report', function () {
    Sanctum::actingAs($this->otherUserAccount, ['*']);
    $this->getJson('/api/reports/purchase-history')
        ->assertForbidden();
});

test('it can render purchase history report page', function () {
    PurchaseOrderItem::factory()->create();

    Sanctum::actingAs($this->testUser, ['*']);
    $this->getJson('/api/reports/purchase-history')
        ->assertOk();
});

test('it can fetch purchase history data via json', function () {
    $supplier = Supplier::factory()->create(['name' => 'Supplier Riwayat']);
    $warehouse = Warehouse::factory()->create([
        'name' => 'Gudang Riwayat',
        'code' => 'WH-RWY',
    ]);
    $product = Product::factory()->create([
        'name' => 'Produk Riwayat',
        'code' => 'PRH-001',
    ]);
    $unit = Unit::factory()->create(['name' => 'PCS']);

    $po = PurchaseOrder::factory()->create([
        'po_number' => 'PO-HIS-001',
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'order_date' => '2026-03-01',
        'status' => 'partially_received',
    ]);

    $poItem = PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $po->id,
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'quantity' => 10,
        'line_total' => 120000,
    ]);

    $confirmedGr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $po->id,
        'warehouse_id' => $warehouse->id,
        'status' => 'confirmed',
        'receipt_date' => '2026-03-05',
    ]);

    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $confirmedGr->id,
        'purchase_order_item_id' => $poItem->id,
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'quantity_received' => 4,
        'quantity_accepted' => 4,
        'quantity_rejected' => 0,
    ]);

    $draftGr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $po->id,
        'warehouse_id' => $warehouse->id,
        'status' => 'draft',
        'receipt_date' => '2026-03-06',
    ]);

    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $draftGr->id,
        'purchase_order_item_id' => $poItem->id,
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'quantity_received' => 3,
        'quantity_accepted' => 3,
        'quantity_rejected' => 0,
    ]);

    Sanctum::actingAs($this->testUser, ['*']);
    $this->getJson('/api/reports/purchase-history')
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data', 1)
            ->where('data.0.purchase_order.po_number', 'PO-HIS-001')
            ->where('data.0.supplier.name', 'Supplier Riwayat')
            ->where('data.0.product.name', 'Produk Riwayat')
            ->where('data.0.warehouse.name', 'Gudang Riwayat')
            ->where('data.0.ordered_quantity', '10.00')
            ->where('data.0.received_quantity', '4.00')
            ->where('data.0.outstanding_quantity', '6.00')
            ->where('data.0.receipt_count', 1)
            ->where('data.0.last_receipt_date', '2026-03-05')
            ->where('data.0.total_purchase_value', '120000.00')
            ->has('meta')
            ->has('links')
        );
});

test('it can filter by supplier product status and date range', function () {
    $supplierA = Supplier::factory()->create(['name' => 'Supplier A']);
    $supplierB = Supplier::factory()->create(['name' => 'Supplier B']);
    $warehouse = Warehouse::factory()->create();
    $productA = Product::factory()->create(['name' => 'Produk A']);
    $productB = Product::factory()->create(['name' => 'Produk B']);
    $unit = Unit::factory()->create();

    $poA = PurchaseOrder::factory()->create([
        'po_number' => 'PO-PH-001',
        'supplier_id' => $supplierA->id,
        'warehouse_id' => $warehouse->id,
        'order_date' => '2026-03-01',
        'status' => 'confirmed',
    ]);

    PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $poA->id,
        'product_id' => $productA->id,
        'unit_id' => $unit->id,
    ]);

    $poB = PurchaseOrder::factory()->create([
        'po_number' => 'PO-PH-002',
        'supplier_id' => $supplierB->id,
        'warehouse_id' => $warehouse->id,
        'order_date' => '2026-03-15',
        'status' => 'draft',
    ]);

    PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $poB->id,
        'product_id' => $productB->id,
        'unit_id' => $unit->id,
    ]);

    Sanctum::actingAs($this->testUser, ['*']);
    $this->getJson('/api/reports/purchase-history?supplier_id=' . $supplierA->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.supplier.id', $supplierA->id);

    Sanctum::actingAs($this->testUser, ['*']);
    $this->getJson('/api/reports/purchase-history?product_id=' . $productA->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.purchase_order.po_number', 'PO-PH-001');

    Sanctum::actingAs($this->testUser, ['*']);
    $this->getJson('/api/reports/purchase-history?status=draft')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.purchase_order.po_number', 'PO-PH-002');

    Sanctum::actingAs($this->testUser, ['*']);
    $this->getJson('/api/reports/purchase-history?start_date=2026-03-10&end_date=2026-03-31')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.purchase_order.po_number', 'PO-PH-002');
});

test('it can export purchase history report', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-04 10:00:00'));
    Excel::fake();
    Storage::fake('public');

    Sanctum::actingAs($this->testUser, ['*']);
    $response = $this->postJson('/api/reports/purchase-history/export')
        ->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');
    expect($filename)->toStartWith('purchase_history_report_2026-03-04_10-00-00_');
    expect($filename)->toEndWith('.xlsx');

    Excel::assertStored('exports/' . $filename, 'public');
    Carbon::setTestNow();
});
