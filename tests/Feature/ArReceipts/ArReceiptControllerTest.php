<?php

use App\Models\Account;
use App\Models\ArReceipt;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('ar-receipts');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'ar_receipt',
        'ar_receipt.create',
        'ar_receipt.edit',
        'ar_receipt.delete',
    ]);
    Sanctum::actingAs($user, ['*']);
});

test('index returns paginated ar receipts', function () {
    ArReceipt::factory()->count(20)->create();

    $response = getJson('/api/ar-receipts?per_page=10');

    $response->assertOk()
        ->assertJsonStructure([
            'data',
            'meta' => ['total', 'per_page', 'current_page'],
        ]);

    expect($response->json('data'))->toHaveCount(10);
});

test('index supports search and filters', function () {
    $customer = Customer::factory()->create();
    $fiscalYear = FiscalYear::factory()->create();

    ArReceipt::factory()->create([
        'receipt_number' => 'RCP-SEARCH-001',
        'customer_id' => $customer->id,
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'draft',
    ]);
    ArReceipt::factory()->confirmed()->create();

    getJson('/api/ar-receipts?search=RCP-SEARCH-001')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    getJson('/api/ar-receipts?customer_id=' . $customer->id)
        ->assertOk()
        ->assertJsonCount(1, 'data');

    getJson('/api/ar-receipts?fiscal_year_id=' . $fiscalYear->id)
        ->assertOk()
        ->assertJsonCount(1, 'data');

    getJson('/api/ar-receipts?status=draft')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('store creates ar receipt with allocations', function () {
    $customer = Customer::factory()->create();
    $branch = Branch::factory()->create();
    $fiscalYear = FiscalYear::factory()->create();
    $bankAccount = Account::factory()->create();
    $invoice = CustomerInvoice::factory()->create([
        'customer_id' => $customer->id,
        'fiscal_year_id' => $fiscalYear->id,
        'grand_total' => 100000,
        'status' => 'sent',
    ]);

    $payload = [
        'customer_id' => $customer->id,
        'branch_id' => $branch->id,
        'fiscal_year_id' => $fiscalYear->id,
        'receipt_date' => '2026-03-06',
        'payment_method' => 'bank_transfer',
        'bank_account_id' => $bankAccount->id,
        'currency' => 'IDR',
        'total_amount' => 50000,
        'status' => 'draft',
        'allocations' => [
            [
                'customer_invoice_id' => $invoice->id,
                'allocated_amount' => 50000,
            ],
        ],
    ];

    $response = postJson('/api/ar-receipts', $payload);

    $response->assertCreated()
        ->assertJsonPath('data.customer.id', $customer->id)
        ->assertJsonPath('data.allocations.0.customer_invoice_id', $invoice->id);

    $id = $response->json('data.id');
    assertDatabaseHas('ar_receipts', ['id' => $id, 'customer_id' => $customer->id]);
    assertDatabaseHas('ar_receipt_allocations', [
        'ar_receipt_id' => $id,
        'customer_invoice_id' => $invoice->id,
    ]);
});

test('show returns ar receipt detail', function () {
    $customer = Customer::factory()->create();
    $fiscalYear = FiscalYear::factory()->create();
    $invoice = CustomerInvoice::factory()->create([
        'customer_id' => $customer->id,
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'sent',
    ]);
    $receipt = ArReceipt::factory()->create([
        'customer_id' => $customer->id,
        'fiscal_year_id' => $fiscalYear->id,
    ]);
    $receipt->allocations()->create([
        'customer_invoice_id' => $invoice->id,
        'allocated_amount' => 30000,
    ]);

    getJson('/api/ar-receipts/' . $receipt->id)
        ->assertOk()
        ->assertJsonPath('data.id', $receipt->id)
        ->assertJsonCount(1, 'data.allocations');
});

test('update modifies ar receipt and allocations', function () {
    $customer = Customer::factory()->create();
    $fiscalYear = FiscalYear::factory()->create();
    $invoice = CustomerInvoice::factory()->create([
        'customer_id' => $customer->id,
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'sent',
    ]);
    $receipt = ArReceipt::factory()->create([
        'customer_id' => $customer->id,
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'draft',
    ]);

    $payload = [
        'status' => 'confirmed',
        'allocations' => [
            [
                'customer_invoice_id' => $invoice->id,
                'allocated_amount' => 75000,
            ],
        ],
    ];

    putJson('/api/ar-receipts/' . $receipt->id, $payload)
        ->assertOk()
        ->assertJsonPath('data.status', 'confirmed')
        ->assertJsonCount(1, 'data.allocations');
});

test('destroy removes ar receipt', function () {
    $receipt = ArReceipt::factory()->create();

    deleteJson('/api/ar-receipts/' . $receipt->id)
        ->assertNoContent();

    assertDatabaseMissing('ar_receipts', ['id' => $receipt->id]);
});
