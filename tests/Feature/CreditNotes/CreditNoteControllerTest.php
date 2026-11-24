<?php

use App\Actions\AccountingPosting\PostCreditNoteJournalAction;
use App\Actions\AccountingPosting\ResolveControlAccountAction;
use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\Account;
use App\Models\Branch;
use App\Models\CoaVersion;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use RuntimeException;

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
    CreditNote::factory()->confirmed()->create();

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

test('store with confirmed status does not post journal (Refs #367)', function () {
    [$customer, $fiscalYear, $invoice, $product] = createCreditNoteWithItem();
    [, $returns] = seedCreditNoteApplyAccounts();

    $response = postJson('/api/credit-notes', [
        'customer_id' => $customer->id,
        'branch_id' => Branch::factory()->create()->id,
        'fiscal_year_id' => $fiscalYear->id,
        'customer_invoice_id' => $invoice->id,
        'credit_note_date' => $fiscalYear->start_date->toDateString(),
        'reason' => 'return',
        'status' => 'confirmed',
        'items' => [
            [
                'product_id' => $product->id,
                'account_id' => $returns->id,
                'description' => 'Retur',
                'quantity' => 1,
                'unit_price' => 250000,
                'tax_percent' => 0,
            ],
        ],
    ])->assertCreated();

    $creditNote = CreditNote::query()->findOrFail($response->json('data.id'));

    expect($creditNote->status)->toBe('confirmed');
    expect($creditNote->journal_entry_id)->toBeNull();
});

test('store with applied status posts journal and reduces invoice amount_due (Refs #367)', function () {
    [$customer, $fiscalYear, , $product] = createCreditNoteWithItem();
    [, $returns] = seedCreditNoteApplyAccounts();

    $invoice = CustomerInvoice::factory()->create([
        'customer_id' => $customer->id,
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'sent',
        'grand_total' => 1000000,
        'amount_received' => 0,
        'credit_note_amount' => 0,
        'amount_due' => 1000000,
    ]);

    $response = postJson('/api/credit-notes', [
        'customer_id' => $customer->id,
        'branch_id' => Branch::factory()->create()->id,
        'fiscal_year_id' => $fiscalYear->id,
        'customer_invoice_id' => $invoice->id,
        'credit_note_date' => $fiscalYear->start_date->toDateString(),
        'reason' => 'return',
        'status' => 'applied',
        'items' => [
            [
                'product_id' => $product->id,
                'account_id' => $returns->id,
                'description' => 'Retur',
                'quantity' => 1,
                'unit_price' => 250000,
                'tax_percent' => 0,
            ],
        ],
    ])->assertCreated()
        ->assertJsonPath('data.status', 'applied');

    $creditNote = CreditNote::query()->findOrFail($response->json('data.id'));

    expect($creditNote->journal_entry_id)->not->toBeNull();
    expect((float) $invoice->refresh()->amount_due)->toBe(750000.0);
});

test('store with applied status rejects amount above invoice outstanding (Refs #367)', function () {
    [$customer, $fiscalYear, , $product] = createCreditNoteWithItem();
    [, $returns] = seedCreditNoteApplyAccounts();

    $invoice = CustomerInvoice::factory()->create([
        'customer_id' => $customer->id,
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'sent',
        'grand_total' => 100000,
        'amount_received' => 0,
        'credit_note_amount' => 0,
        'amount_due' => 100000,
    ]);

    postJson('/api/credit-notes', [
        'customer_id' => $customer->id,
        'branch_id' => Branch::factory()->create()->id,
        'fiscal_year_id' => $fiscalYear->id,
        'customer_invoice_id' => $invoice->id,
        'credit_note_date' => $fiscalYear->start_date->toDateString(),
        'reason' => 'return',
        'status' => 'applied',
        'items' => [
            [
                'product_id' => $product->id,
                'account_id' => $returns->id,
                'description' => 'Retur',
                'quantity' => 1,
                'unit_price' => 250000,
                'tax_percent' => 0,
            ],
        ],
    ])->assertStatus(422)
        ->assertJsonPath('message', 'Credit note amount exceeds invoice outstanding.');

    assertDatabaseMissing('credit_notes', ['customer_invoice_id' => $invoice->id]);
});

test('store with applied status rolls back the credit note when journal posting fails (Refs #367)', function () {
    [$customer, $fiscalYear, , $product] = createCreditNoteWithItem();
    [, $returns] = seedCreditNoteApplyAccounts();

    $invoice = CustomerInvoice::factory()->create([
        'customer_id' => $customer->id,
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'sent',
        'grand_total' => 1000000,
        'amount_received' => 0,
        'credit_note_amount' => 0,
        'amount_due' => 1000000,
    ]);

    bindFailingCreditNotePosting($this->app);

    postJson('/api/credit-notes', [
        'customer_id' => $customer->id,
        'branch_id' => Branch::factory()->create()->id,
        'fiscal_year_id' => $fiscalYear->id,
        'customer_invoice_id' => $invoice->id,
        'credit_note_date' => $fiscalYear->start_date->toDateString(),
        'reason' => 'return',
        'status' => 'applied',
        'items' => [
            [
                'product_id' => $product->id,
                'account_id' => $returns->id,
                'description' => 'Retur',
                'quantity' => 1,
                'unit_price' => 250000,
                'tax_percent' => 0,
            ],
        ],
    ])->assertStatus(500);

    assertDatabaseMissing('credit_notes', ['customer_invoice_id' => $invoice->id, 'status' => 'applied']);
});

test('update to applied status posts journal once and reduces invoice amount_due (Refs #367)', function () {
    [$customer, $fiscalYear, , $product] = createCreditNoteWithItem();
    [, $returns] = seedCreditNoteApplyAccounts();

    $invoice = CustomerInvoice::factory()->create([
        'customer_id' => $customer->id,
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'sent',
        'grand_total' => 1000000,
        'amount_received' => 0,
        'credit_note_amount' => 0,
        'amount_due' => 1000000,
    ]);

    $creditNote = CreditNote::factory()->confirmed()->create([
        'customer_id' => $customer->id,
        'fiscal_year_id' => $fiscalYear->id,
        'customer_invoice_id' => $invoice->id,
        'credit_note_date' => $fiscalYear->start_date->toDateString(),
        'grand_total' => 250000,
        'subtotal' => 250000,
        'tax_amount' => 0,
    ]);

    putJson('/api/credit-notes/' . $creditNote->id, [
        'status' => 'applied',
        'items' => [
            [
                'product_id' => $product->id,
                'account_id' => $returns->id,
                'description' => 'Retur',
                'quantity' => 1,
                'unit_price' => 250000,
                'tax_percent' => 0,
            ],
        ],
    ])->assertOk()
        ->assertJsonPath('data.status', 'applied');

    $creditNote->refresh();
    expect($creditNote->journal_entry_id)->not->toBeNull();
    expect((float) $invoice->refresh()->amount_due)->toBe(750000.0);
});

/**
 * Mengganti aksi posting credit note dengan versi yang selalu gagal, untuk
 * membuktikan rollback transaksi saat posting jurnal error.
 */
function bindFailingCreditNotePosting(Application $app): void
{
    $app->bind(PostCreditNoteJournalAction::class, function ($app) {
        $createJournal = $app->make(CreateJournalEntryAction::class);
        $resolveAccount = $app->make(ResolveControlAccountAction::class);

        return new class($createJournal, $resolveAccount) extends PostCreditNoteJournalAction
        {
            public function execute(CreditNote $creditNote): ?JournalEntry
            {
                throw new RuntimeException('Simulated journal posting failure');
            }
        };
    });
}

/**
 * Apply memposting jurnal (Dr retur, Cr AR 11200), jadi fixture wajib punya
 * COA aktif berisi akun AR 11200 dan akun retur, serta item credit note.
 *
 * @return array{0: Account, 1: Account}
 */
function seedCreditNoteApplyAccounts(): array
{
    $coaVersion = CoaVersion::factory()->create(['status' => 'active']);

    $ar = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '11200',
        'name' => 'Accounts Receivable',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);

    $returns = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '41100',
        'name' => 'Sales Returns and Allowances',
        'type' => 'revenue',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);

    return [$ar, $returns];
}

test('apply transitions confirmed credit note to applied and reduces invoice amount_due', function () {
    [$customer, $fiscalYear] = createCreditNoteWithItem();
    [, $returns] = seedCreditNoteApplyAccounts();

    $invoice = CustomerInvoice::factory()->create([
        'customer_id' => $customer->id,
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'sent',
        'grand_total' => 1000000,
        'amount_received' => 0,
        'credit_note_amount' => 0,
        'amount_due' => 1000000,
    ]);

    $creditNote = CreditNote::factory()->confirmed()->create([
        'customer_id' => $customer->id,
        'fiscal_year_id' => $fiscalYear->id,
        'customer_invoice_id' => $invoice->id,
        'credit_note_date' => $fiscalYear->start_date->toDateString(),
        'grand_total' => 250000,
        'subtotal' => 250000,
        'tax_amount' => 0,
    ]);
    $creditNote->items()->create([
        'account_id' => $returns->id,
        'description' => 'Retur',
        'quantity' => 1,
        'unit_price' => 250000,
        'tax_percent' => 0,
        'line_total' => 250000,
    ]);

    postJson('/api/credit-notes/' . $creditNote->id . '/apply')
        ->assertOk()
        ->assertJsonPath('data.status', 'applied');

    assertDatabaseHas('credit_notes', [
        'id' => $creditNote->id,
        'status' => 'applied',
    ]);

    $invoice->refresh();
    expect((float) $invoice->credit_note_amount)->toBe(250000.0);
    expect((float) $invoice->amount_due)->toBe(750000.0);
    expect($invoice->status)->toBe('partially_paid');
});

test('apply settles invoice to paid when credit note covers full outstanding', function () {
    [$customer, $fiscalYear] = createCreditNoteWithItem();
    [, $returns] = seedCreditNoteApplyAccounts();

    $invoice = CustomerInvoice::factory()->create([
        'customer_id' => $customer->id,
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'sent',
        'grand_total' => 500000,
        'amount_received' => 0,
        'credit_note_amount' => 0,
        'amount_due' => 500000,
    ]);

    $creditNote = CreditNote::factory()->confirmed()->create([
        'customer_id' => $customer->id,
        'fiscal_year_id' => $fiscalYear->id,
        'customer_invoice_id' => $invoice->id,
        'credit_note_date' => $fiscalYear->start_date->toDateString(),
        'grand_total' => 500000,
        'subtotal' => 500000,
        'tax_amount' => 0,
    ]);
    $creditNote->items()->create([
        'account_id' => $returns->id,
        'description' => 'Retur',
        'quantity' => 1,
        'unit_price' => 500000,
        'tax_percent' => 0,
        'line_total' => 500000,
    ]);

    postJson('/api/credit-notes/' . $creditNote->id . '/apply')
        ->assertOk()
        ->assertJsonPath('data.status', 'applied');

    $invoice->refresh();
    expect((float) $invoice->amount_due)->toBe(0.0);
    expect($invoice->status)->toBe('paid');
});

test('apply rejects credit note that is not confirmed', function () {
    [$customer, $fiscalYear, $invoice] = createCreditNoteWithItem();

    $creditNote = CreditNote::factory()->create([
        'customer_id' => $customer->id,
        'fiscal_year_id' => $fiscalYear->id,
        'customer_invoice_id' => $invoice->id,
        'status' => 'draft',
    ]);

    postJson('/api/credit-notes/' . $creditNote->id . '/apply')
        ->assertStatus(422)
        ->assertJsonPath('message', 'Only confirmed credit notes can be applied.');

    assertDatabaseHas('credit_notes', [
        'id' => $creditNote->id,
        'status' => 'draft',
    ]);
});

test('apply rejects credit note without linked invoice', function () {
    $creditNote = CreditNote::factory()->confirmed()->create([
        'customer_invoice_id' => null,
    ]);

    postJson('/api/credit-notes/' . $creditNote->id . '/apply')
        ->assertStatus(422)
        ->assertJsonPath('message', 'Credit note must be linked to an invoice.');
});

test('apply rejects credit note that exceeds invoice outstanding amount', function () {
    [$customer, $fiscalYear] = createCreditNoteWithItem();

    $invoice = CustomerInvoice::factory()->create([
        'customer_id' => $customer->id,
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'sent',
        'grand_total' => 100000,
        'amount_received' => 0,
        'credit_note_amount' => 0,
        'amount_due' => 100000,
    ]);

    $creditNote = CreditNote::factory()->confirmed()->create([
        'customer_id' => $customer->id,
        'fiscal_year_id' => $fiscalYear->id,
        'customer_invoice_id' => $invoice->id,
        'grand_total' => 250000,
    ]);

    postJson('/api/credit-notes/' . $creditNote->id . '/apply')
        ->assertStatus(422)
        ->assertJsonPath('message', 'Credit note amount exceeds invoice outstanding.');

    assertDatabaseHas('credit_notes', [
        'id' => $creditNote->id,
        'status' => 'confirmed',
    ]);
});
