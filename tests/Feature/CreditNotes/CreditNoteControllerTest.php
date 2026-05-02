<?php

use App\Models\Account;
use App\Models\Branch;
use App\Models\CreditNote;
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

uses(RefreshDatabase::class)->group('credit-notes');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'credit_note',
        'credit_note.create',
        'credit_note.edit',
        'credit_note.delete',
    ]);
    Sanctum::actingAs($user, ['*']);
});

function createCreditNoteWithItem(): array
{
    $customer = Customer::factory()->create();
    $fiscalYear = FiscalYear::factory()->create();
    $invoice = CustomerInvoice::factory()->create([
        'customer_id' => $customer->id,
        'fiscal_year_id' => $fiscalYear->id,
    ]);
    $product = Product::factory()->create();
    $unit = Unit::factory()->create();

    return [$customer, $fiscalYear, $invoice, $product, $unit];
}

test('index returns paginated credit notes', function () {
    CreditNote::factory()->count(20)->create();

    $response = getJson('/api/credit-notes?per_page=10');

    $response->assertOk()
        ->assertJsonStructure([
            'data',
            'meta' => ['total', 'per_page', 'current_page'],
        ]);

    expect($response->json('data'))->toHaveCount(10);
});

test('index supports search and filters', function () {
    [$customer, $fiscalYear, $invoice] = createCreditNoteWithItem();

    CreditNote::factory()->create([
        'credit_note_number' => 'CN-SEARCH-001',
        'customer_id' => $customer->id,
        'fiscal_year_id' => $fiscalYear->id,
        'customer_invoice_id' => $invoice->id,
        'status' => 'draft',
    ]);
    CreditNote::factory()->create();

    getJson('/api/credit-notes?search=CN-SEARCH-001')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    getJson('/api/credit-notes?customer_id=' . $customer->id)
        ->assertOk()
        ->assertJsonCount(1, 'data');

    getJson('/api/credit-notes?fiscal_year_id=' . $fiscalYear->id)
        ->assertOk()
        ->assertJsonCount(1, 'data');

    getJson('/api/credit-notes?status=draft')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('store creates credit note with items', function () {
    [$customer, $fiscalYear, $invoice, $product, $unit] = createCreditNoteWithItem();

    $branch = Branch::factory()->create();
    $account = Account::factory()->create();

    $payload = [
        'customer_id' => $customer->id,
        'branch_id' => $branch->id,
        'fiscal_year_id' => $fiscalYear->id,
        'customer_invoice_id' => $invoice->id,
        'credit_note_date' => '2026-03-06',
        'reason' => 'return',
        'status' => 'draft',
        'items' => [
            [
                'product_id' => $product->id,
                'account_id' => $account->id,
                'description' => 'Returned item',
                'quantity' => 2,
                'unit_price' => 10000,
                'tax_percent' => 11,
            ],
        ],
    ];

    $response = postJson('/api/credit-notes', $payload);

    $response->assertCreated()
        ->assertJsonPath('data.customer.id', $customer->id)
        ->assertJsonPath('data.items.0.product_id', $product->id);

    $id = $response->json('data.id');
    assertDatabaseHas('credit_notes', ['id' => $id, 'customer_id' => $customer->id]);
    assertDatabaseHas('credit_note_items', [
        'credit_note_id' => $id,
        'product_id' => $product->id,
    ]);
});

test('show returns credit note detail', function () {
    [$customer, $fiscalYear, $invoice, $product, $unit] = createCreditNoteWithItem();
    $creditNote = CreditNote::factory()->create([
        'customer_id' => $customer->id,
        'fiscal_year_id' => $fiscalYear->id,
        'customer_invoice_id' => $invoice->id,
    ]);
    $creditNote->items()->create([
        'product_id' => $product->id,
        'account_id' => Account::factory()->create()->id,
        'description' => 'Test CN item',
        'quantity' => 1,
        'unit_price' => 5000,
        'tax_percent' => 11,
        'line_total' => 5550,
    ]);

    getJson('/api/credit-notes/' . $creditNote->id)
        ->assertOk()
        ->assertJsonPath('data.id', $creditNote->id)
        ->assertJsonCount(1, 'data.items');
});

test('update modifies credit note and items', function () {
    [$customer, $fiscalYear, $invoice, $product, $unit] = createCreditNoteWithItem();
    $creditNote = CreditNote::factory()->create([
        'customer_id' => $customer->id,
        'fiscal_year_id' => $fiscalYear->id,
        'customer_invoice_id' => $invoice->id,
        'status' => 'draft',
    ]);

    $account = Account::factory()->create();
    $payload = [
        'status' => 'confirmed',
        'items' => [
            [
                'product_id' => $product->id,
                'account_id' => $account->id,
                'description' => 'Updated CN item',
                'quantity' => 3,
                'unit_price' => 8000,
                'tax_percent' => 11,
            ],
        ],
    ];

    putJson('/api/credit-notes/' . $creditNote->id, $payload)
        ->assertOk()
        ->assertJsonPath('data.status', 'confirmed')
        ->assertJsonCount(1, 'data.items');
});

test('destroy removes credit note', function () {
    $creditNote = CreditNote::factory()->create();

    deleteJson('/api/credit-notes/' . $creditNote->id)
        ->assertNoContent();

    assertDatabaseMissing('credit_notes', ['id' => $creditNote->id]);
});
