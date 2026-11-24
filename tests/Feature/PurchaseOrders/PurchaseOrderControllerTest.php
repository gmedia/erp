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

test('index accepts a list of statuses', function () {
    PurchaseOrder::factory()->create(['status' => 'confirmed', 'currency' => 'IDR']);
    PurchaseOrder::factory()->create(['status' => 'partially_received', 'currency' => 'IDR']);
    PurchaseOrder::factory()->create(['status' => 'draft', 'currency' => 'IDR']);

    getJson('/api/purchase-orders?status[]=confirmed&status[]=partially_received&per_page=50')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    getJson('/api/purchase-orders?status[]=bogus')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status.0');
});

test('store rejects manually creating purchase order with confirmed or rejected status', function (string $status) {
    $payload = [
        'supplier_id' => Supplier::factory()->create()->id,
        'warehouse_id' => Warehouse::factory()->create()->id,
        'order_date' => '2026-03-05',
        'currency' => 'IDR',
        'status' => $status,
        'items' => [
            [
                'product_id' => Product::factory()->create()->id,
                'unit_id' => Unit::factory()->create()->id,
                'quantity' => 1,
                'unit_price' => 5000,
                'discount_percent' => 0,
                'tax_percent' => 0,
            ],
        ],
    ];

    postJson('/api/purchase-orders', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');
})->with(['confirmed', 'rejected']);

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
    $purchaseOrder = PurchaseOrder::factory()->create(['status' => 'draft']);
    $product = Product::factory()->create();
    $unit = Unit::factory()->create();

    $payload = [
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
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonCount(1, 'data.items');
});

test('update rejects manually setting purchase order status to confirmed or rejected', function (string $status) {
    $purchaseOrder = PurchaseOrder::factory()->create(['status' => 'draft']);

    putJson('/api/purchase-orders/' . $purchaseOrder->id, ['status' => $status])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');

    expect($purchaseOrder->fresh()->status)->toBe('draft');
})->with(['confirmed', 'rejected']);

test('store only accepts draft status for purchase order creation', function (string $status) {
    $payload = [
        'supplier_id' => Supplier::factory()->create()->id,
        'warehouse_id' => Warehouse::factory()->create()->id,
        'order_date' => '2026-03-05',
        'currency' => 'IDR',
        'status' => $status,
        'items' => [
            [
                'product_id' => Product::factory()->create()->id,
                'unit_id' => Unit::factory()->create()->id,
                'quantity' => 1,
                'unit_price' => 1000,
            ],
        ],
    ];

    postJson('/api/purchase-orders', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');
})->with(['confirmed', 'rejected', 'partially_received', 'fully_received', 'pending_approval', 'closed']);

test('update rejects manual purchase order status jumps that bypass approval or receiving', function (string $status) {
    $purchaseOrder = PurchaseOrder::factory()->create(['status' => 'draft']);

    putJson('/api/purchase-orders/' . $purchaseOrder->id, ['status' => $status])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');

    expect($purchaseOrder->fresh()->status)->toBe('draft');
})->with(['pending_approval', 'partially_received', 'fully_received', 'closed']);

test('update rejects editing items of a purchase order awaiting approval or confirmed', function (string $status) {
    $purchaseOrder = PurchaseOrder::factory()->create(['status' => $status]);

    putJson('/api/purchase-orders/' . $purchaseOrder->id, [
        'items' => [
            [
                'product_id' => Product::factory()->create()->id,
                'unit_id' => Unit::factory()->create()->id,
                'quantity' => 999,
                'unit_price' => 1000,
            ],
        ],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('items');
})->with(['pending_approval', 'confirmed']);

test('locked items message is translated for en and id', function (string $locale, string $expected) {
    app()->setLocale($locale);
    $purchaseOrder = PurchaseOrder::factory()->create(['status' => 'confirmed']);

    $response = putJson('/api/purchase-orders/' . $purchaseOrder->id, [
        'items' => [
            [
                'product_id' => Product::factory()->create()->id,
                'unit_id' => Unit::factory()->create()->id,
                'quantity' => 999,
                'unit_price' => 1000,
            ],
        ],
    ])->assertUnprocessable();

    expect($response->json('errors.items.0'))->toBe($expected);
})->with([
    ['en', 'Items can only be changed while the document is still a draft.'],
    ['id', 'Item hanya dapat diubah selama dokumen masih draft.'],
]);

test('update rejects changing supplier or warehouse of a purchase order awaiting approval', function () {
    $purchaseOrder = PurchaseOrder::factory()->create(['status' => 'pending_approval']);

    putJson('/api/purchase-orders/' . $purchaseOrder->id, [
        'supplier_id' => Supplier::factory()->create()->id,
    ])->assertUnprocessable()->assertJsonValidationErrors('supplier_id');

    putJson('/api/purchase-orders/' . $purchaseOrder->id, [
        'warehouse_id' => Warehouse::factory()->create()->id,
    ])->assertUnprocessable()->assertJsonValidationErrors('warehouse_id');
});

test('update allows resending unchanged header values while purchase order awaits approval', function () {
    $purchaseOrder = PurchaseOrder::factory()->create(['status' => 'pending_approval']);

    putJson('/api/purchase-orders/' . $purchaseOrder->id, [
        'supplier_id' => $purchaseOrder->supplier_id,
        'warehouse_id' => $purchaseOrder->warehouse_id,
        'currency' => $purchaseOrder->currency,
        'notes' => 'catatan saja',
    ])->assertOk();
});

test('update keeps already confirmed purchase order confirmed', function () {
    $purchaseOrder = PurchaseOrder::factory()->create(['status' => 'confirmed', 'approved_at' => now()]);

    putJson('/api/purchase-orders/' . $purchaseOrder->id, ['status' => 'confirmed', 'notes' => 'edit'])
        ->assertOk()
        ->assertJsonPath('data.status', 'confirmed');
});

test('destroy removes purchase order', function () {
    $purchaseOrder = PurchaseOrder::factory()->create();

    deleteJson('/api/purchase-orders/' . $purchaseOrder->id)
        ->assertNoContent();

    assertDatabaseMissing('purchase_orders', ['id' => $purchaseOrder->id]);
});

test('store rejects unsupported currency', function () {
    $supplier = Supplier::factory()->create();
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create();
    $unit = Unit::factory()->create();

    $payload = [
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'order_date' => '2026-03-05',
        'currency' => 'USD',
        'status' => 'draft',
        'items' => [
            [
                'product_id' => $product->id,
                'unit_id' => $unit->id,
                'quantity' => 1,
                'unit_price' => 1000,
            ],
        ],
    ];

    postJson('/api/purchase-orders', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['currency']);
});
