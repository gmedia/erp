<?php

use App\Actions\AccountingPosting\PostCustomerInvoiceJournalAction;
use App\Actions\AccountingPosting\PostSupplierBillJournalAction;
use App\Models\Account;
use App\Models\ApPayment;
use App\Models\ApPaymentAllocation;
use App\Models\CoaVersion;
use App\Models\CreditNote;
use App\Models\CustomerInvoice;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\SupplierBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('issue-375');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'customer_invoice', 'customer_invoice.edit',
        'supplier_bill', 'supplier_bill.edit',
    ]);
    Sanctum::actingAs($user, ['*']);

    FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);
});

/**
 * @return array{ar: Account, revenue: Account, ap: Account, expense: Account}
 */
function cancelTestAccounts(): array
{
    $coa = CoaVersion::factory()->create(['status' => 'active']);

    $make = static fn (
        string $code,
        string $name,
        string $type,
        string $normal,
    ): Account => Account::factory()->create([
        'coa_version_id' => $coa->id,
        'code' => $code,
        'name' => $name,
        'type' => $type,
        'normal_balance' => $normal,
        'is_active' => true,
    ]);

    return [
        'ar' => $make('11200', 'Accounts Receivable', 'asset', 'debit'),
        'revenue' => $make('41000', 'Sales Revenue', 'revenue', 'credit'),
        'ap' => $make('21100', 'Accounts Payable', 'liability', 'credit'),
        'expense' => $make('51100', 'Operating Expense', 'expense', 'debit'),
    ];
}

function cancelTestInvoice(array $accounts, string $status = 'sent'): CustomerInvoice
{
    $invoice = CustomerInvoice::factory()->create([
        'invoice_number' => 'INV-2026-000375',
        'invoice_date' => '2026-03-15',
        'status' => $status,
        'subtotal' => 500000,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'grand_total' => 500000,
        'amount_received' => 0,
        'credit_note_amount' => 0,
        'amount_due' => 500000,
    ]);

    $invoice->items()->create([
        'account_id' => $accounts['revenue']->id,
        'description' => 'Jasa',
        'quantity' => 1,
        'unit_price' => 500000,
        'discount_percent' => 0,
        'tax_percent' => 0,
        'line_total' => 500000,
    ]);

    return $invoice;
}

function cancelTestBill(array $accounts, string $status = 'confirmed'): SupplierBill
{
    $bill = SupplierBill::factory()->create([
        'bill_number' => 'BILL-2026-000375',
        'bill_date' => '2026-03-15',
        'status' => $status,
        'subtotal' => 300000,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'grand_total' => 300000,
        'amount_paid' => 0,
        'amount_due' => 300000,
    ]);

    $bill->items()->create([
        'account_id' => $accounts['expense']->id,
        'description' => 'Biaya',
        'quantity' => 1,
        'unit_price' => 300000,
        'discount_percent' => 0,
        'tax_percent' => 0,
        'line_total' => 300000,
    ]);

    return $bill;
}

function lineNetCreditFor(JournalEntry $entry, int $accountId): float
{
    return round(
        JournalEntryLine::query()
            ->where('journal_entry_id', $entry->id)
            ->where('account_id', $accountId)
            ->get(['debit', 'credit'])
            ->sum(fn ($l) => (float) $l->credit - (float) $l->debit),
        2,
    );
}

test('batal invoice terposting membalik baris jurnal asli, jurnal asli tetap posted', function () {
    $accounts = cancelTestAccounts();
    $invoice = cancelTestInvoice($accounts);

    $original = app(PostCustomerInvoiceJournalAction::class)->execute($invoice->fresh());

    putJson("/api/customer-invoices/{$invoice->id}", ['status' => 'cancelled'])->assertOk();

    $reversal = JournalEntry::query()
        ->where('source_type', CustomerInvoice::class)
        ->where('source_id', $invoice->id)
        ->where('id', '!=', $original->id)
        ->first();

    expect($reversal)->not->toBeNull()
        ->and(lineNetCreditFor($reversal, $accounts['ar']->id))->toBe(500000.0)
        ->and(lineNetCreditFor($reversal, $accounts['revenue']->id))->toBe(-500000.0)
        ->and(JournalEntry::find($original->id)->status)->toBe('posted');

    assertDatabaseHas('customer_invoices', ['id' => $invoice->id, 'status' => 'cancelled']);
});

test('batal invoice dua kali tidak membuat pembalik kedua', function () {
    $accounts = cancelTestAccounts();
    $invoice = cancelTestInvoice($accounts);
    $original = app(PostCustomerInvoiceJournalAction::class)->execute($invoice->fresh());

    putJson("/api/customer-invoices/{$invoice->id}", ['status' => 'cancelled'])->assertOk();

    // Batal kedua ditolak: status tersimpan sudah cancelled (final), tidak boleh memposting pembalik lagi.
    putJson("/api/customer-invoices/{$invoice->id}", ['status' => 'cancelled'])->assertStatus(422);

    $reversals = JournalEntry::query()
        ->where('source_type', CustomerInvoice::class)
        ->where('source_id', $invoice->id)
        ->where('id', '!=', $original->id)
        ->count();

    expect($reversals)->toBe(1);
});

test('batal invoice ditolak 422 bila masih punya credit note applied', function () {
    $accounts = cancelTestAccounts();
    $invoice = cancelTestInvoice($accounts);
    app(PostCustomerInvoiceJournalAction::class)->execute($invoice->fresh());

    CreditNote::factory()->create([
        'customer_invoice_id' => $invoice->id,
        'customer_id' => $invoice->customer_id,
        'branch_id' => $invoice->branch_id,
        'fiscal_year_id' => $invoice->fiscal_year_id,
        'status' => 'applied',
        'grand_total' => 100000,
    ]);

    putJson("/api/customer-invoices/{$invoice->id}", ['status' => 'cancelled'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('status');

    expect($invoice->refresh()->status)->toBe('sent');
});

test('batal invoice ditolak 422 bila periode jurnal sudah tertutup', function () {
    $accounts = cancelTestAccounts();
    $invoice = cancelTestInvoice($accounts);
    app(PostCustomerInvoiceJournalAction::class)->execute($invoice->fresh());

    FiscalYear::query()->update(['status' => 'closed']);

    putJson("/api/customer-invoices/{$invoice->id}", ['status' => 'cancelled'])->assertStatus(422);

    expect($invoice->refresh()->status)->toBe('sent');
});

test('batal bill ditolak 422 bila masih punya alokasi pembayaran confirmed', function () {
    $accounts = cancelTestAccounts();
    $bill = cancelTestBill($accounts);
    app(PostSupplierBillJournalAction::class)->execute($bill->fresh());

    $payment = ApPayment::factory()->create([
        'status' => 'confirmed',
        'supplier_id' => $bill->supplier_id,
        'branch_id' => $bill->branch_id,
        'fiscal_year_id' => $bill->fiscal_year_id,
    ]);
    ApPaymentAllocation::factory()->create([
        'ap_payment_id' => $payment->id,
        'supplier_bill_id' => $bill->id,
    ]);

    putJson("/api/supplier-bills/{$bill->id}", ['status' => 'cancelled'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('status');

    expect($bill->refresh()->status)->toBe('confirmed');
});

test('batal bill terposting membalik baris jurnal asli, jurnal asli tetap posted', function () {
    $accounts = cancelTestAccounts();
    $bill = cancelTestBill($accounts);

    $original = app(PostSupplierBillJournalAction::class)->execute($bill->fresh());

    putJson("/api/supplier-bills/{$bill->id}", ['status' => 'cancelled'])->assertOk();

    $reversal = JournalEntry::query()
        ->where('source_type', SupplierBill::class)
        ->where('source_id', $bill->id)
        ->where('id', '!=', $original->id)
        ->first();

    expect($reversal)->not->toBeNull()
        ->and(lineNetCreditFor($reversal, $accounts['ap']->id))->toBe(-300000.0)
        ->and(JournalEntry::find($original->id)->status)->toBe('posted');

    assertDatabaseHas('supplier_bills', ['id' => $bill->id, 'status' => 'cancelled']);
});
