<?php

use App\Models\Account;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\FiscalYear;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('customer-invoices');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'customer_invoice',
        'customer_invoice.create',
        'customer_invoice.edit',
        'customer_invoice.delete',
    ]);
    Sanctum::actingAs($user, ['*']);
});

function createCustomerInvoiceWithItem(): array
{
    $customer = Customer::factory()->create();
    $branch = Branch::factory()->create();
    $fiscalYear = FiscalYear::factory()->create();
    $product = Product::factory()->create();
    $unit = Unit::factory()->create();
    $account = Account::factory()->create();

    return [$customer, $branch, $fiscalYear, $product, $unit, $account];
}

test('index returns paginated customer invoices', function () {
    CustomerInvoice::factory()->count(20)->create();

    $response = getJson('/api/customer-invoices?per_page=10');

    $response->assertOk()
        ->assertJsonStructure([
            'data',
            'meta' => ['total', 'per_page', 'current_page'],
        ]);

    expect($response->json('data'))->toHaveCount(10);
});

test('index supports search and filters', function () {
    [$customer, $branch] = createCustomerInvoiceWithItem();

    CustomerInvoice::factory()->create([
        'invoice_number' => 'INV-SEARCH-001',
        'customer_id' => $customer->id,
        'branch_id' => $branch->id,
        'status' => 'draft',
    ]);
    CustomerInvoice::factory()->create();

    getJson('/api/customer-invoices?search=INV-SEARCH-001')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    getJson('/api/customer-invoices?customer_id=' . $customer->id)
        ->assertOk()
        ->assertJsonCount(1, 'data');

    getJson('/api/customer-invoices?branch_id=' . $branch->id)
        ->assertOk()
        ->assertJsonCount(1, 'data');

    getJson('/api/customer-invoices?status=draft')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('store creates customer invoice with items', function () {
    [$customer, $branch, $fiscalYear, $product, $unit, $account] = createCustomerInvoiceWithItem();

    $payload = [
        'customer_id' => $customer->id,
        'branch_id' => $branch->id,
        'fiscal_year_id' => $fiscalYear->id,
        'invoice_date' => '2026-03-06',
        'due_date' => '2026-04-06',
        'status' => 'draft',
        'items' => [
            [
                'product_id' => $product->id,
                'unit_id' => $unit->id,
                'account_id' => $account->id,
                'quantity' => 5,
                'unit_price' => 10000,
                'discount_percent' => 0,
                'tax_percent' => 11,
                'line_total' => 55500,
            ],
        ],
    ];

    $response = postJson('/api/customer-invoices', $payload);

    $response->assertCreated()
        ->assertJsonPath('data.customer.id', $customer->id)
        ->assertJsonPath('data.items.0.product.id', $product->id);

    $id = $response->json('data.id');
    assertDatabaseHas('customer_invoices', ['id' => $id, 'customer_id' => $customer->id]);
    assertDatabaseHas('customer_invoice_items', [
        'customer_invoice_id' => $id,
        'product_id' => $product->id,
    ]);
});

test('show returns customer invoice detail', function () {
    [$customer, $branch, $fiscalYear, $product, $unit, $account] = createCustomerInvoiceWithItem();
    $invoice = CustomerInvoice::factory()->create([
        'customer_id' => $customer->id,
        'branch_id' => $branch->id,
        'fiscal_year_id' => $fiscalYear->id,
    ]);
    $invoice->items()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'account_id' => $account->id,
        'quantity' => 3,
        'unit_price' => 1000,
        'discount_percent' => 0,
        'tax_percent' => 11,
        'line_total' => 3330,
    ]);

    getJson('/api/customer-invoices/' . $invoice->id)
        ->assertOk()
        ->assertJsonPath('data.id', $invoice->id)
        ->assertJsonCount(1, 'data.items');
});

test('update modifies customer invoice and sets sent_by when status changes to sent', function () {
    [$customer, $branch, $fiscalYear, $product, $unit, $account] = createCustomerInvoiceWithItem();
    $invoice = CustomerInvoice::factory()->create([
        'customer_id' => $customer->id,
        'branch_id' => $branch->id,
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'draft',
        'sent_by' => null,
    ]);

    $payload = [
        'status' => 'sent',
        'items' => [
            [
                'product_id' => $product->id,
                'unit_id' => $unit->id,
                'account_id' => $account->id,
                'quantity' => 7,
                'unit_price' => 10000,
                'discount_percent' => 0,
                'tax_percent' => 11,
                'line_total' => 77700,
            ],
        ],
    ];

    putJson('/api/customer-invoices/' . $invoice->id, $payload)
        ->assertOk()
        ->assertJsonPath('data.status', 'sent');

    $invoice->refresh();
    expect($invoice->sent_by)->not->toBeNull();
});

test('destroy removes customer invoice', function () {
    $invoice = CustomerInvoice::factory()->create();

    deleteJson('/api/customer-invoices/' . $invoice->id)
        ->assertNoContent();

    assertDatabaseMissing('customer_invoices', ['id' => $invoice->id]);
});
