<?php

use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierReturn;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('supplier-returns');

beforeEach(function () {
    $this->user = createTestUserWithPermissions([
        'supplier_return',
        'supplier_return.create',
        'supplier_return.edit',
        'supplier_return.delete',
    ]);
    actingAs($this->user);
});

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
    [$purchaseOrder, $goodsReceipt, $goodsReceiptItem, $supplier, $warehouse, $product, $unit] = createGoodsReceiptItemForSupplierReturn();

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
    assertDatabaseHas('supplier_return_items', ['supplier_return_id' => $id, 'goods_receipt_item_id' => $goodsReceiptItem->id]);
});

test('show returns supplier return detail', function () {
    [$purchaseOrder, $goodsReceipt, $goodsReceiptItem, $supplier, $warehouse, $product, $unit] = createGoodsReceiptItemForSupplierReturn();
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
    [$purchaseOrder, $goodsReceipt, $goodsReceiptItem, $supplier, $warehouse, $product, $unit] = createGoodsReceiptItemForSupplierReturn();
    $supplierReturn = SupplierReturn::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'goods_receipt_id' => $goodsReceipt->id,
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
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

test('destroy removes supplier return', function () {
    $supplierReturn = SupplierReturn::factory()->create();

    deleteJson('/api/supplier-returns/' . $supplierReturn->id)
        ->assertNoContent();

    assertDatabaseMissing('supplier_returns', ['id' => $supplierReturn->id]);
});
