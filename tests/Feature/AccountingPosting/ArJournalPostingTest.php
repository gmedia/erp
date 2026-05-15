<?php

use App\Actions\AccountingPosting\PostArReceiptJournalAction;
use App\Actions\AccountingPosting\PostCustomerInvoiceJournalAction;
use App\Models\Account;
use App\Models\ArReceipt;
use App\Models\CoaVersion;
use App\Models\CustomerInvoice;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('ar-journal-posting');

function makeArPostingFiscalYear(): FiscalYear
{
    return FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);
}

function makeArPostingChartOfAccounts(): array
{
    $coaVersion = CoaVersion::factory()->create(['status' => 'active']);

    $arAccount = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '11200',
        'name' => 'Accounts Receivable',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);

    $revenueAccount = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '41000',
        'name' => 'Sales Revenue',
        'type' => 'revenue',
        'normal_balance' => 'credit',
        'is_active' => true,
    ]);

    $bankAccount = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '11110',
        'name' => 'Cash in Bank',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);

    return [
        'coa_version' => $coaVersion,
        'ar' => $arAccount,
        'revenue' => $revenueAccount,
        'bank' => $bankAccount,
    ];
}

test('PostCustomerInvoiceJournalAction creates a balanced posted journal entry', function () {
    $fiscalYear = makeArPostingFiscalYear();
    $accounts = makeArPostingChartOfAccounts();

    $invoice = CustomerInvoice::factory()->sent()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'invoice_number' => 'INV-2026-000001',
        'invoice_date' => '2026-03-15',
        'subtotal' => 1000000,
        'tax_amount' => 110000,
        'discount_amount' => 0,
        'grand_total' => 1110000,
        'amount_received' => 0,
        'credit_note_amount' => 0,
        'amount_due' => 1110000,
    ]);

    $invoice->items()->create([
        'account_id' => $accounts['revenue']->id,
        'description' => 'Service fee',
        'quantity' => 1,
        'unit_price' => 1000000,
        'discount_percent' => 0,
        'tax_percent' => 11,
        'line_total' => 1110000,
    ]);

    $action = app(PostCustomerInvoiceJournalAction::class);

    $journalEntry = $action->execute($invoice->fresh());

    expect($journalEntry)->not->toBeNull()
        ->and($journalEntry->status)->toBe('posted')
        ->and($journalEntry->journal_type)->toBe('system')
        ->and($journalEntry->source_type)->toBe(CustomerInvoice::class)
        ->and($journalEntry->source_id)->toBe($invoice->id)
        ->and((float) $journalEntry->total_debit)->toBe(1110000.0)
        ->and((float) $journalEntry->total_credit)->toBe(1110000.0);

    assertDatabaseHas('customer_invoices', [
        'id' => $invoice->id,
        'journal_entry_id' => $journalEntry->id,
    ]);

    $arLine = $journalEntry->lines()->where('account_id', $accounts['ar']->id)->first();
    expect((float) $arLine->debit)->toBe(1110000.0)
        ->and((float) $arLine->credit)->toBe(0.0);

    $revenueLine = $journalEntry->lines()->where('account_id', $accounts['revenue']->id)->first();
    expect((float) $revenueLine->credit)->toBe(1110000.0)
        ->and((float) $revenueLine->debit)->toBe(0.0);
});

test('PostCustomerInvoiceJournalAction is idempotent and returns existing entry', function () {
    makeArPostingFiscalYear();
    $accounts = makeArPostingChartOfAccounts();

    $invoice = CustomerInvoice::factory()->sent()->create([
        'invoice_date' => '2026-03-15',
        'grand_total' => 500000,
        'amount_due' => 500000,
    ]);
    $invoice->items()->create([
        'account_id' => $accounts['revenue']->id,
        'description' => 'Item',
        'quantity' => 1,
        'unit_price' => 500000,
        'discount_percent' => 0,
        'tax_percent' => 0,
        'line_total' => 500000,
    ]);

    $action = app(PostCustomerInvoiceJournalAction::class);

    $first = $action->execute($invoice->fresh());
    $second = $action->execute($invoice->fresh());

    expect($second->id)->toBe($first->id);
    expect(JournalEntry::count())->toBe(1);
});

test('PostCustomerInvoiceJournalAction returns null when invoice is not sent', function () {
    makeArPostingFiscalYear();
    makeArPostingChartOfAccounts();

    $invoice = CustomerInvoice::factory()->create(['status' => 'draft']);

    $action = app(PostCustomerInvoiceJournalAction::class);

    expect($action->execute($invoice))->toBeNull();
    expect(JournalEntry::count())->toBe(0);
});

test('PostCustomerInvoiceJournalAction throws when no items exist', function () {
    makeArPostingFiscalYear();
    makeArPostingChartOfAccounts();

    $invoice = CustomerInvoice::factory()->sent()->create(['invoice_date' => '2026-03-15']);

    $action = app(PostCustomerInvoiceJournalAction::class);

    expect(fn () => $action->execute($invoice->fresh()))
        ->toThrow(Illuminate\Validation\ValidationException::class);
});

test('PostCustomerInvoiceJournalAction throws when no active COA version exists', function () {
    makeArPostingFiscalYear();

    $invoice = CustomerInvoice::factory()->sent()->create(['invoice_date' => '2026-03-15']);
    $orphanAccount = Account::factory()->create();
    $invoice->items()->create([
        'account_id' => $orphanAccount->id,
        'description' => 'Item',
        'quantity' => 1,
        'unit_price' => 100000,
        'discount_percent' => 0,
        'tax_percent' => 0,
        'line_total' => 100000,
    ]);

    $action = app(PostCustomerInvoiceJournalAction::class);

    expect(fn () => $action->execute($invoice->fresh()))
        ->toThrow(Illuminate\Validation\ValidationException::class);
});

test('PostArReceiptJournalAction creates a balanced posted journal entry', function () {
    $fiscalYear = makeArPostingFiscalYear();
    $accounts = makeArPostingChartOfAccounts();

    $arReceipt = ArReceipt::factory()->confirmed()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'receipt_number' => 'RCV-2026-000001',
        'receipt_date' => '2026-03-20',
        'bank_account_id' => $accounts['bank']->id,
        'total_amount' => 1110000,
    ]);

    $action = app(PostArReceiptJournalAction::class);

    $journalEntry = $action->execute($arReceipt->fresh());

    expect($journalEntry)->not->toBeNull()
        ->and($journalEntry->status)->toBe('posted')
        ->and($journalEntry->journal_type)->toBe('system')
        ->and($journalEntry->source_type)->toBe(ArReceipt::class)
        ->and($journalEntry->source_id)->toBe($arReceipt->id);

    assertDatabaseHas('ar_receipts', [
        'id' => $arReceipt->id,
        'journal_entry_id' => $journalEntry->id,
    ]);

    $bankLine = $journalEntry->lines()->where('account_id', $accounts['bank']->id)->first();
    expect((float) $bankLine->debit)->toBe(1110000.0)
        ->and((float) $bankLine->credit)->toBe(0.0);

    $arLine = $journalEntry->lines()->where('account_id', $accounts['ar']->id)->first();
    expect((float) $arLine->credit)->toBe(1110000.0)
        ->and((float) $arLine->debit)->toBe(0.0);
});

test('PostArReceiptJournalAction is idempotent', function () {
    makeArPostingFiscalYear();
    $accounts = makeArPostingChartOfAccounts();

    $arReceipt = ArReceipt::factory()->confirmed()->create([
        'receipt_date' => '2026-03-20',
        'bank_account_id' => $accounts['bank']->id,
        'total_amount' => 500000,
    ]);

    $action = app(PostArReceiptJournalAction::class);

    $first = $action->execute($arReceipt->fresh());
    $second = $action->execute($arReceipt->fresh());

    expect($second->id)->toBe($first->id);
    expect(JournalEntry::count())->toBe(1);
});

test('PostArReceiptJournalAction returns null when receipt is not confirmed', function () {
    makeArPostingFiscalYear();
    makeArPostingChartOfAccounts();

    $arReceipt = ArReceipt::factory()->create(['status' => 'draft']);

    $action = app(PostArReceiptJournalAction::class);

    expect($action->execute($arReceipt))->toBeNull();
    expect(JournalEntry::count())->toBe(0);
});

test('PostArReceiptJournalAction throws on non-positive total amount', function () {
    makeArPostingFiscalYear();
    $accounts = makeArPostingChartOfAccounts();

    $arReceipt = ArReceipt::factory()->confirmed()->create([
        'receipt_date' => '2026-03-20',
        'bank_account_id' => $accounts['bank']->id,
        'total_amount' => 0,
    ]);

    $action = app(PostArReceiptJournalAction::class);

    expect(fn () => $action->execute($arReceipt->fresh()))
        ->toThrow(Illuminate\Validation\ValidationException::class);
});

test('CustomerInvoiceController update triggers journal posting on sent transition', function () {
    $user = createTestUserWithPermissions([
        'customer_invoice',
        'customer_invoice.create',
        'customer_invoice.edit',
        'customer_invoice.delete',
    ]);
    Sanctum::actingAs($user, ['*']);

    makeArPostingFiscalYear();
    $accounts = makeArPostingChartOfAccounts();

    $invoice = CustomerInvoice::factory()->create([
        'status' => 'draft',
        'invoice_date' => '2026-03-15',
        'subtotal' => 800000,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'grand_total' => 800000,
        'amount_received' => 0,
        'credit_note_amount' => 0,
        'amount_due' => 800000,
        'sent_by' => null,
        'sent_at' => null,
    ]);

    $payload = [
        'status' => 'sent',
        'items' => [
            [
                'account_id' => $accounts['revenue']->id,
                'description' => 'Service',
                'quantity' => 1,
                'unit_price' => 800000,
                'discount_percent' => 0,
                'tax_percent' => 0,
            ],
        ],
    ];

    putJson("/api/customer-invoices/{$invoice->id}", $payload)
        ->assertOk()
        ->assertJsonPath('data.status', 'sent');

    $invoice->refresh();
    expect($invoice->journal_entry_id)->not->toBeNull();

    assertDatabaseHas('journal_entries', [
        'id' => $invoice->journal_entry_id,
        'status' => 'posted',
        'journal_type' => 'system',
        'source_type' => CustomerInvoice::class,
        'source_id' => $invoice->id,
    ]);
});

test('CustomerInvoiceController update does not double-post on subsequent saves', function () {
    $user = createTestUserWithPermissions([
        'customer_invoice',
        'customer_invoice.create',
        'customer_invoice.edit',
        'customer_invoice.delete',
    ]);
    Sanctum::actingAs($user, ['*']);

    makeArPostingFiscalYear();
    $accounts = makeArPostingChartOfAccounts();

    $invoice = CustomerInvoice::factory()->create([
        'status' => 'draft',
        'invoice_date' => '2026-03-15',
        'grand_total' => 500000,
        'amount_due' => 500000,
        'sent_by' => null,
        'sent_at' => null,
    ]);

    $items = [[
        'account_id' => $accounts['revenue']->id,
        'description' => 'Service',
        'quantity' => 1,
        'unit_price' => 500000,
        'discount_percent' => 0,
        'tax_percent' => 0,
    ]];

    putJson("/api/customer-invoices/{$invoice->id}", [
        'status' => 'sent',
        'items' => $items,
    ])->assertOk();

    putJson("/api/customer-invoices/{$invoice->id}", [
        'notes' => 'edited after sent',
    ])->assertOk();

    expect(JournalEntry::where('source_type', CustomerInvoice::class)->count())->toBe(1);
});

test('ArReceiptController update triggers journal posting on confirm transition', function () {
    $user = createTestUserWithPermissions([
        'ar_receipt',
        'ar_receipt.create',
        'ar_receipt.edit',
        'ar_receipt.delete',
    ]);
    Sanctum::actingAs($user, ['*']);

    makeArPostingFiscalYear();
    $accounts = makeArPostingChartOfAccounts();

    $arReceipt = ArReceipt::factory()->create([
        'status' => 'draft',
        'receipt_date' => '2026-03-20',
        'bank_account_id' => $accounts['bank']->id,
        'total_amount' => 750000,
        'confirmed_by' => null,
        'confirmed_at' => null,
    ]);

    putJson("/api/ar-receipts/{$arReceipt->id}", [
        'status' => 'confirmed',
    ])->assertOk()->assertJsonPath('data.status', 'confirmed');

    $arReceipt->refresh();
    expect($arReceipt->journal_entry_id)->not->toBeNull();

    assertDatabaseHas('journal_entries', [
        'id' => $arReceipt->journal_entry_id,
        'status' => 'posted',
        'journal_type' => 'system',
        'source_type' => ArReceipt::class,
        'source_id' => $arReceipt->id,
    ]);
});

test('ArReceiptController update does not double-post on subsequent saves', function () {
    $user = createTestUserWithPermissions([
        'ar_receipt',
        'ar_receipt.create',
        'ar_receipt.edit',
        'ar_receipt.delete',
    ]);
    Sanctum::actingAs($user, ['*']);

    makeArPostingFiscalYear();
    $accounts = makeArPostingChartOfAccounts();

    $arReceipt = ArReceipt::factory()->create([
        'status' => 'draft',
        'receipt_date' => '2026-03-20',
        'bank_account_id' => $accounts['bank']->id,
        'total_amount' => 250000,
        'confirmed_by' => null,
        'confirmed_at' => null,
    ]);

    putJson("/api/ar-receipts/{$arReceipt->id}", ['status' => 'confirmed'])->assertOk();
    putJson("/api/ar-receipts/{$arReceipt->id}", ['notes' => 'reconciled note'])->assertOk();

    expect(JournalEntry::where('source_type', ArReceipt::class)->count())->toBe(1);
});
