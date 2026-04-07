<?php

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('purchase-requests');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'purchase_request',
        'purchase_request.create',
        'purchase_request.edit',
        'purchase_request.delete',
    ]);
    Sanctum::actingAs($user, ['*']);
});

test('index returns paginated purchase requests', function () {
    PurchaseRequest::factory()->count(20)->create();

    $response = getJson('/api/purchase-requests?per_page=10');

    $response->assertOk()
        ->assertJsonStructure([
            'data',
            'meta' => ['total', 'per_page', 'current_page'],
        ]);

    expect($response->json('data'))->toHaveCount(10);
});

test('index supports search and filters', function () {
    $branch = Branch::factory()->create();
    $department = Department::factory()->create();
    $requester = Employee::factory()->create();

    PurchaseRequest::factory()->create([
        'pr_number' => 'PR-SEARCH-001',
        'branch_id' => $branch->id,
        'department_id' => $department->id,
        'requested_by' => $requester->id,
        'priority' => 'high',
        'status' => 'draft',
    ]);
    PurchaseRequest::factory()->create([
        'priority' => 'low',
        'status' => 'approved',
    ]);

    getJson('/api/purchase-requests?search=PR-SEARCH-001')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    getJson('/api/purchase-requests?branch_id=' . $branch->id)
        ->assertOk()
        ->assertJsonCount(1, 'data');

    getJson('/api/purchase-requests?department_id=' . $department->id)
        ->assertOk()
        ->assertJsonCount(1, 'data');

    getJson('/api/purchase-requests?requested_by=' . $requester->id)
        ->assertOk()
        ->assertJsonCount(1, 'data');

    getJson('/api/purchase-requests?priority=high&status=draft')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('store creates purchase request with items', function () {
    $branch = Branch::factory()->create();
    $department = Department::factory()->create();
    $requester = Employee::factory()->create();
    $product = Product::factory()->create();
    $unit = Unit::factory()->create();

    $payload = [
        'branch_id' => $branch->id,
        'department_id' => $department->id,
        'requested_by' => $requester->id,
        'request_date' => '2026-03-05',
        'required_date' => '2026-03-08',
        'priority' => 'normal',
        'status' => 'draft',
        'notes' => 'Need office supplies',
        'items' => [
            [
                'product_id' => $product->id,
                'unit_id' => $unit->id,
                'quantity' => 10,
                'estimated_unit_price' => 5000,
                'notes' => 'Urgent',
            ],
        ],
    ];

    $response = postJson('/api/purchase-requests', $payload);

    $response->assertCreated()
        ->assertJsonPath('data.priority', 'normal')
        ->assertJsonPath('data.items.0.product.id', $product->id);

    $id = $response->json('data.id');
    assertDatabaseHas('purchase_requests', ['id' => $id, 'branch_id' => $branch->id]);
    assertDatabaseHas('purchase_request_items', ['purchase_request_id' => $id, 'product_id' => $product->id]);
});

test('show returns purchase request detail', function () {
    $purchaseRequest = PurchaseRequest::factory()->create();
    $product = Product::factory()->create();
    $unit = Unit::factory()->create();
    $purchaseRequest->items()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'quantity' => 3,
    ]);

    getJson('/api/purchase-requests/' . $purchaseRequest->id)
        ->assertOk()
        ->assertJsonPath('data.id', $purchaseRequest->id)
        ->assertJsonCount(1, 'data.items');
});

test('update modifies purchase request and items', function () {
    $purchaseRequest = PurchaseRequest::factory()->create();
    $product = Product::factory()->create();
    $unit = Unit::factory()->create();

    $payload = [
        'priority' => 'urgent',
        'status' => 'approved',
        'items' => [
            [
                'product_id' => $product->id,
                'unit_id' => $unit->id,
                'quantity' => 5,
                'estimated_unit_price' => 10000,
            ],
        ],
    ];

    putJson('/api/purchase-requests/' . $purchaseRequest->id, $payload)
        ->assertOk()
        ->assertJsonPath('data.priority', 'urgent')
        ->assertJsonPath('data.status', 'approved')
        ->assertJsonCount(1, 'data.items');
});

test('destroy removes purchase request', function () {
    $purchaseRequest = PurchaseRequest::factory()->create(['status' => 'draft']);

    deleteJson('/api/purchase-requests/' . $purchaseRequest->id)
        ->assertNoContent();

    assertDatabaseMissing('purchase_requests', ['id' => $purchaseRequest->id]);
});
