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

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('goods-receipt-report');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['goods_receipt_report']);
    $this->otherUser = createTestUserWithPermissions([]);
});

test('it requires permission to access goods receipt report', function () {
    Sanctum::actingAs($this->otherUser, ['*']);
    getJson('/api/reports/goods-receipt')
        ->assertForbidden();
});

test('it can fetch aggregated goods receipt report data', function () {
    $supplier = Supplier::factory()->create(['name' => 'Supplier GR']);
    $warehouse = Warehouse::factory()->create([
        'name' => 'Gudang GR',
        'code' => 'WH-GR',
    ]);
    $product = Product::factory()->create([
        'name' => 'Produk GR',
        'code' => 'PGR-001',
    ]);
    $unit = Unit::factory()->create(['name' => 'PCS']);

    $po = PurchaseOrder::factory()->create([
        'po_number' => 'PO-GR-001',
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
    ]);

    $poItem = PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $po->id,
        'product_id' => $product->id,
        'unit_id' => $unit->id,
    ]);

    $gr = GoodsReceipt::factory()->create([
        'gr_number' => 'GR-001',
        'purchase_order_id' => $po->id,
        'warehouse_id' => $warehouse->id,
        'receipt_date' => '2026-03-10',
        'status' => 'confirmed',
    ]);

    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr->id,
        'purchase_order_item_id' => $poItem->id,
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'quantity_received' => 10,
        'quantity_accepted' => 8,
        'quantity_rejected' => 2,
        'unit_price' => 1000,
    ]);

    Sanctum::actingAs($this->user, ['*']);
    getJson('/api/reports/goods-receipt')
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data', 1)
            ->where('data.0.goods_receipt.gr_number', 'GR-001')
            ->where('data.0.purchase_order.po_number', 'PO-GR-001')
            ->where('data.0.supplier.name', 'Supplier GR')
            ->where('data.0.warehouse.name', 'Gudang GR')
            ->where('data.0.item_count', 1)
            ->where('data.0.total_received_quantity', '10.00')
            ->where('data.0.total_accepted_quantity', '8.00')
            ->where('data.0.total_rejected_quantity', '2.00')
            ->where('data.0.total_receipt_value', '10000.00')
            ->has('meta')
            ->has('links')
        );
});

test('it can filter by supplier warehouse product status and date range', function () {
    $supplierA = Supplier::factory()->create(['name' => 'Supplier A']);
    $supplierB = Supplier::factory()->create(['name' => 'Supplier B']);
    $warehouseA = Warehouse::factory()->create(['name' => 'Warehouse A']);
    $warehouseB = Warehouse::factory()->create(['name' => 'Warehouse B']);
    $productA = Product::factory()->create(['name' => 'Product A']);
    $productB = Product::factory()->create(['name' => 'Product B']);
    $unit = Unit::factory()->create();

    $poA = PurchaseOrder::factory()->create([
        'po_number' => 'PO-GRF-001',
        'supplier_id' => $supplierA->id,
        'warehouse_id' => $warehouseA->id,
    ]);
    $poItemA = PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $poA->id,
        'product_id' => $productA->id,
        'unit_id' => $unit->id,
    ]);
    $grA = GoodsReceipt::factory()->create([
        'gr_number' => 'GR-FLT-001',
        'purchase_order_id' => $poA->id,
        'warehouse_id' => $warehouseA->id,
        'status' => 'confirmed',
        'receipt_date' => '2026-03-01',
    ]);
    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $grA->id,
        'purchase_order_item_id' => $poItemA->id,
        'product_id' => $productA->id,
        'unit_id' => $unit->id,
    ]);

    $poB = PurchaseOrder::factory()->create([
        'po_number' => 'PO-GRF-002',
        'supplier_id' => $supplierB->id,
        'warehouse_id' => $warehouseB->id,
    ]);
    $poItemB = PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $poB->id,
        'product_id' => $productB->id,
        'unit_id' => $unit->id,
    ]);
    $grB = GoodsReceipt::factory()->create([
        'gr_number' => 'GR-FLT-002',
        'purchase_order_id' => $poB->id,
        'warehouse_id' => $warehouseB->id,
        'status' => 'draft',
        'receipt_date' => '2026-03-15',
    ]);
    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $grB->id,
        'purchase_order_item_id' => $poItemB->id,
        'product_id' => $productB->id,
        'unit_id' => $unit->id,
    ]);

    Sanctum::actingAs($this->user, ['*']);

    getJson('/api/reports/goods-receipt?supplier_id=' . $supplierA->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.goods_receipt.gr_number', 'GR-FLT-001');

    getJson('/api/reports/goods-receipt?warehouse_id=' . $warehouseB->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.goods_receipt.gr_number', 'GR-FLT-002');

    getJson('/api/reports/goods-receipt?product_id=' . $productA->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.goods_receipt.gr_number', 'GR-FLT-001');

    getJson('/api/reports/goods-receipt?status=draft')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.goods_receipt.gr_number', 'GR-FLT-002');

    getJson('/api/reports/goods-receipt?start_date=2026-03-10&end_date=2026-03-31')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.goods_receipt.gr_number', 'GR-FLT-002');
});

test('it can export goods receipt report', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-04 10:00:00'));
    Excel::fake();
    Storage::fake('public');

    Sanctum::actingAs($this->user, ['*']);
    $response = postJson('/api/reports/goods-receipt/export')
        ->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');
    expect($filename)->toStartWith('goods_receipt_report_2026-03-04_10-00-00_');
    expect($filename)->toEndWith('.xlsx');

    Excel::assertStored('exports/' . $filename, 'public');
    Carbon::setTestNow();
});

test('it can export goods receipt report as csv', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-04 10:00:00'));
    Excel::fake();
    Storage::fake('public');

    Sanctum::actingAs($this->user, ['*']);
    $response = postJson('/api/reports/goods-receipt/export', [
        'format' => 'csv',
    ])
        ->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');
    expect($filename)->toStartWith('goods_receipt_report_2026-03-04_10-00-00_');
    expect($filename)->toEndWith('.csv');

    Excel::assertStored('exports/' . $filename, 'public');
    Carbon::setTestNow();
});
