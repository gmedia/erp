<?php

use App\Models\Account;
use App\Models\Branch;
use App\Models\FiscalYear;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierBill;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('supplier-bills');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'supplier_bill',
        'supplier_bill.create',
        'supplier_bill.edit',
        'supplier_bill.delete',
    ]);
    Sanctum::actingAs($user, ['*']);
});

test('index returns paginated supplier bills', function () {
    SupplierBill::factory()->count(20)->create();

    $response = getJson('/api/supplier-bills?per_page=10');

    $response->assertOk()
        ->assertJsonStructure([
            'data',
            'meta' => ['total', 'per_page', 'current_page'],
        ]);

    expect($response->json('data'))->toHaveCount(10);
});

test('index supports search and filters', function () {
    $supplier = Supplier::factory()->create();
    $branch = Branch::factory()->create();
    $fiscalYear = FiscalYear::factory()->create();

    SupplierBill::factory()->create([
        'bill_number' => 'BILL-SEARCH-001',
        'supplier_id' => $supplier->id,
        'branch_id' => $branch->id,
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'draft',
        'currency' => 'IDR',
    ]);
    SupplierBill::factory()->create([
        'status' => 'confirmed',
        'currency' => 'USD',
    ]);

    getJson('/api/supplier-bills?search=BILL-SEARCH-001')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    getJson('/api/supplier-bills?supplier_id=' . $supplier->id)
        ->assertOk()
        ->assertJsonCount(1, 'data');

    getJson('/api/supplier-bills?branch_id=' . $branch->id)
        ->assertOk()
        ->assertJsonCount(1, 'data');

    getJson('/api/supplier-bills?fiscal_year_id=' . $fiscalYear->id)
        ->assertOk()
        ->assertJsonCount(1, 'data');

    getJson('/api/supplier-bills?status=draft&currency=IDR')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('store creates supplier bill with items', function () {
    $supplier = Supplier::factory()->create();
    $branch = Branch::factory()->create();
    $fiscalYear = FiscalYear::factory()->create();
    $product = Product::factory()->create();
    $unit = Unit::factory()->create();
    $account = Account::factory()->create();

    $payload = [
        'supplier_id' => $supplier->id,
        'branch_id' => $branch->id,
        'fiscal_year_id' => $fiscalYear->id,
        'supplier_invoice_number' => 'INV-001',
        'supplier_invoice_date' => '2026-03-05',
        'bill_date' => '2026-03-05',
        'due_date' => '2026-04-05',
        'payment_terms' => 'Net 30',
        'currency' => 'IDR',
        'status' => 'draft',
        'notes' => 'Initial bill',
        'items' => [
            [
                'product_id' => $product->id,
                'account_id' => $account->id,
                'description' => 'Product purchase',
                'quantity' => 10,
                'unit_price' => 5000,
                'discount_percent' => 5,
                'tax_percent' => 11,
            ],
        ],
    ];

    $response = postJson('/api/supplier-bills', $payload);

    $response->assertCreated()
        ->assertJsonPath('data.currency', 'IDR')
        ->assertJsonPath('data.items.0.product_id', $product->id);

    $id = $response->json('data.id');
    assertDatabaseHas('supplier_bills', ['id' => $id, 'supplier_id' => $supplier->id]);
    assertDatabaseHas('supplier_bill_items', ['supplier_bill_id' => $id, 'product_id' => $product->id]);
});

test('show returns supplier bill detail', function () {
    $supplierBill = SupplierBill::factory()->create();
    $product = Product::factory()->create();
    $account = Account::factory()->create();
    $supplierBill->items()->create([
        'product_id' => $product->id,
        'account_id' => $account->id,
        'description' => 'Test item',
        'quantity' => 3,
        'unit_price' => 1000,
        'line_total' => 3000,
    ]);

    getJson('/api/supplier-bills/' . $supplierBill->id)
        ->assertOk()
        ->assertJsonPath('data.id', $supplierBill->id)
        ->assertJsonCount(1, 'data.items');
});

test('update modifies supplier bill and items', function () {
    $supplierBill = SupplierBill::factory()->create();
    $product = Product::factory()->create();
    $account = Account::factory()->create();

    $payload = [
        'status' => 'confirmed',
        'items' => [
            [
                'product_id' => $product->id,
                'account_id' => $account->id,
                'description' => 'Updated item',
                'quantity' => 5,
                'unit_price' => 10000,
                'discount_percent' => 0,
                'tax_percent' => 11,
            ],
        ],
    ];

    putJson('/api/supplier-bills/' . $supplierBill->id, $payload)
        ->assertOk()
        ->assertJsonPath('data.status', 'confirmed')
        ->assertJsonCount(1, 'data.items');
});

test('destroy removes supplier bill', function () {
    $supplierBill = SupplierBill::factory()->create();

    deleteJson('/api/supplier-bills/' . $supplierBill->id)
        ->assertNoContent();

    assertDatabaseMissing('supplier_bills', ['id' => $supplierBill->id]);
});
