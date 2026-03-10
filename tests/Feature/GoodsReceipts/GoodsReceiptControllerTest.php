<?php

use App\Models\Employee;
use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\PurchaseOrder;
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

uses(RefreshDatabase::class)->group('goods-receipts');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'goods_receipt',
        'goods_receipt.create',
        'goods_receipt.edit',
        'goods_receipt.delete',
    ]);
    Sanctum::actingAs($user, ['*']);
});

function createPurchaseOrderWithItemForGoodsReceipt(): array
{
    $purchaseOrder = PurchaseOrder::factory()->create();
    $product = Product::factory()->create();
    $unit = Unit::factory()->create();

    $item = $purchaseOrder->items()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'quantity' => 10,
        'unit_price' => 5000,
        'discount_percent' => 0,
        'tax_percent' => 11,
        'line_total' => 55500,
    ]);

    return [$purchaseOrder, $item, $product, $unit];
}

test('index returns paginated goods receipts', function () {
    GoodsReceipt::factory()->count(20)->create();

    $response = getJson('/api/goods-receipts?per_page=10');

    $response->assertOk()
        ->assertJsonStructure([
            'data',
            'meta' => ['total', 'per_page', 'current_page'],
        ]);

    expect($response->json('data'))->toHaveCount(10);
});

test('index supports search and filters', function () {
    [$purchaseOrder] = createPurchaseOrderWithItemForGoodsReceipt();
    $warehouse = Warehouse::factory()->create();
    $receiver = Employee::factory()->create();

    GoodsReceipt::factory()->create([
        'gr_number' => 'GR-SEARCH-001',
        'purchase_order_id' => $purchaseOrder->id,
        'warehouse_id' => $warehouse->id,
        'received_by' => $receiver->id,
        'status' => 'draft',
    ]);
    GoodsReceipt::factory()->create();

    getJson('/api/goods-receipts?search=GR-SEARCH-001')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    getJson('/api/goods-receipts?purchase_order_id=' . $purchaseOrder->id)
        ->assertOk()
        ->assertJsonCount(1, 'data');

    getJson('/api/goods-receipts?warehouse_id=' . $warehouse->id)
        ->assertOk()
        ->assertJsonCount(1, 'data');

    getJson('/api/goods-receipts?received_by=' . $receiver->id)
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('index supports sorting by supplier delivery note', function () {
    GoodsReceipt::factory()->create([
        'supplier_delivery_note' => 'SJ-AAA',
    ]);
    GoodsReceipt::factory()->create([
        'supplier_delivery_note' => 'SJ-BBB',
    ]);

    getJson('/api/goods-receipts?sort_by=supplier_delivery_note&sort_direction=desc')
        ->assertOk();
});

test('store creates goods receipt with items', function () {
    [$purchaseOrder, $purchaseOrderItem, $product, $unit] = createPurchaseOrderWithItemForGoodsReceipt();
    $warehouse = Warehouse::factory()->create();

    $payload = [
        'purchase_order_id' => $purchaseOrder->id,
        'warehouse_id' => $warehouse->id,
        'receipt_date' => '2026-03-06',
        'supplier_delivery_note' => 'SJ-001',
        'status' => 'draft',
        'items' => [
            [
                'purchase_order_item_id' => $purchaseOrderItem->id,
                'product_id' => $product->id,
                'unit_id' => $unit->id,
                'quantity_received' => 5,
                'quantity_accepted' => 5,
                'quantity_rejected' => 0,
                'unit_price' => 5000,
            ],
        ],
    ];

    $response = postJson('/api/goods-receipts', $payload);

    $response->assertCreated()
        ->assertJsonPath('data.purchase_order.id', $purchaseOrder->id)
        ->assertJsonPath('data.items.0.product.id', $product->id);

    $id = $response->json('data.id');
    assertDatabaseHas('goods_receipts', ['id' => $id, 'purchase_order_id' => $purchaseOrder->id]);
    assertDatabaseHas('goods_receipt_items', ['goods_receipt_id' => $id, 'purchase_order_item_id' => $purchaseOrderItem->id]);
});

test('show returns goods receipt detail', function () {
    [$purchaseOrder, $purchaseOrderItem, $product, $unit] = createPurchaseOrderWithItemForGoodsReceipt();
    $goodsReceipt = GoodsReceipt::factory()->create(['purchase_order_id' => $purchaseOrder->id]);
    $goodsReceipt->items()->create([
        'purchase_order_item_id' => $purchaseOrderItem->id,
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'quantity_received' => 3,
        'quantity_accepted' => 3,
        'quantity_rejected' => 0,
        'unit_price' => 1000,
    ]);

    getJson('/api/goods-receipts/' . $goodsReceipt->id)
        ->assertOk()
        ->assertJsonPath('data.id', $goodsReceipt->id)
        ->assertJsonCount(1, 'data.items');
});

test('update modifies goods receipt and items', function () {
    [$purchaseOrder, $purchaseOrderItem, $product, $unit] = createPurchaseOrderWithItemForGoodsReceipt();
    $goodsReceipt = GoodsReceipt::factory()->create(['purchase_order_id' => $purchaseOrder->id]);

    $payload = [
        'status' => 'confirmed',
        'items' => [
            [
                'purchase_order_item_id' => $purchaseOrderItem->id,
                'product_id' => $product->id,
                'unit_id' => $unit->id,
                'quantity_received' => 7,
                'quantity_accepted' => 6,
                'quantity_rejected' => 1,
                'unit_price' => 10000,
            ],
        ],
    ];

    putJson('/api/goods-receipts/' . $goodsReceipt->id, $payload)
        ->assertOk()
        ->assertJsonPath('data.status', 'confirmed')
        ->assertJsonCount(1, 'data.items');
});

test('destroy removes goods receipt', function () {
    $goodsReceipt = GoodsReceipt::factory()->create();

    deleteJson('/api/goods-receipts/' . $goodsReceipt->id)
        ->assertNoContent();

    assertDatabaseMissing('goods_receipts', ['id' => $goodsReceipt->id]);
});
