<?php

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
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

uses(RefreshDatabase::class)->group('purchase-orders');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'purchase_order',
        'purchase_order.create',
        'purchase_order.edit',
        'purchase_order.delete',
    ]);
    Sanctum::actingAs($user, ['*']);
});

test('index returns paginated purchase orders', function () {
    PurchaseOrder::factory()->count(20)->create();

    $response = getJson('/api/purchase-orders?per_page=10');

    $response->assertOk()
        ->assertJsonStructure([
            'data',
            'meta' => ['total', 'per_page', 'current_page'],
        ]);

    expect($response->json('data'))->toHaveCount(10);
});

test('index supports search and filters', function () {
    $supplier = Supplier::factory()->create();
    $warehouse = Warehouse::factory()->create();

    PurchaseOrder::factory()->create([
        'po_number' => 'PO-SEARCH-001',
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'status' => 'draft',
        'currency' => 'IDR',
    ]);
    PurchaseOrder::factory()->create([
        'status' => 'confirmed',
        'currency' => 'USD',
    ]);

    getJson('/api/purchase-orders?search=PO-SEARCH-001')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    getJson('/api/purchase-orders?supplier_id=' . $supplier->id)
        ->assertOk()
        ->assertJsonCount(1, 'data');

    getJson('/api/purchase-orders?warehouse_id=' . $warehouse->id)
        ->assertOk()
        ->assertJsonCount(1, 'data');

    getJson('/api/purchase-orders?status=draft&currency=IDR')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('store creates purchase order with items', function () {
    $supplier = Supplier::factory()->create();
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create();
    $unit = Unit::factory()->create();

    $payload = [
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'order_date' => '2026-03-05',
        'expected_delivery_date' => '2026-03-10',
        'payment_terms' => 'Net 30',
        'currency' => 'IDR',
        'status' => 'draft',
        'notes' => 'Initial PO',
        'items' => [
            [
                'product_id' => $product->id,
                'unit_id' => $unit->id,
                'quantity' => 10,
                'unit_price' => 5000,
                'discount_percent' => 5,
                'tax_percent' => 11,
            ],
        ],
    ];

    $response = postJson('/api/purchase-orders', $payload);

    $response->assertCreated()
        ->assertJsonPath('data.currency', 'IDR')
        ->assertJsonPath('data.items.0.product.id', $product->id);

    $id = $response->json('data.id');
    assertDatabaseHas('purchase_orders', ['id' => $id, 'supplier_id' => $supplier->id]);
    assertDatabaseHas('purchase_order_items', ['purchase_order_id' => $id, 'product_id' => $product->id]);
});

test('show returns purchase order detail', function () {
    $purchaseOrder = PurchaseOrder::factory()->create();
    $product = Product::factory()->create();
    $unit = Unit::factory()->create();
    $purchaseOrder->items()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'quantity' => 3,
        'unit_price' => 1000,
        'line_total' => 3000,
    ]);

    getJson('/api/purchase-orders/' . $purchaseOrder->id)
        ->assertOk()
        ->assertJsonPath('data.id', $purchaseOrder->id)
        ->assertJsonCount(1, 'data.items');
});

test('update modifies purchase order and items', function () {
    $purchaseOrder = PurchaseOrder::factory()->create();
    $product = Product::factory()->create();
    $unit = Unit::factory()->create();

    $payload = [
        'status' => 'confirmed',
        'items' => [
            [
                'product_id' => $product->id,
                'unit_id' => $unit->id,
                'quantity' => 5,
                'unit_price' => 10000,
                'discount_percent' => 0,
                'tax_percent' => 11,
            ],
        ],
    ];

    putJson('/api/purchase-orders/' . $purchaseOrder->id, $payload)
        ->assertOk()
        ->assertJsonPath('data.status', 'confirmed')
        ->assertJsonCount(1, 'data.items');
});

test('destroy removes purchase order', function () {
    $purchaseOrder = PurchaseOrder::factory()->create();

    deleteJson('/api/purchase-orders/' . $purchaseOrder->id)
        ->assertNoContent();

    assertDatabaseMissing('purchase_orders', ['id' => $purchaseOrder->id]);
});
