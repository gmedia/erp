<?php

use App\Actions\AccountingPosting\PostApPaymentJournalAction;
use App\Actions\AccountingPosting\PostArReceiptJournalAction;
use App\Actions\AccountingPosting\PostCustomerInvoiceJournalAction;
use App\Actions\AccountingPosting\PostSupplierBillJournalAction;
use App\Actions\ApPayments\SyncApPaymentAllocationsAction;
use App\Actions\ArReceipts\SyncArReceiptAllocationsAction;
use App\Actions\CreditNotes\ApplyCreditNoteAction;
use App\Models\Account;
use App\Models\ApPayment;
use App\Models\ApPaymentAllocation;
use App\Models\ArReceipt;
use App\Models\ArReceiptAllocation;
use App\Models\Branch;
use App\Models\CoaVersion;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Supplier;
use App\Models\SupplierBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('issue-375');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'ar_receipt', 'ar_receipt.edit',
        'ap_payment', 'ap_payment.edit',
        'credit_note', 'credit_note.edit',
    ]);
    Sanctum::actingAs($user, ['*']);

    FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);

    $coa = CoaVersion::factory()->create(['status' => 'active']);
    $make = static fn (string $code, string $name, string $type, string $normal): Account => Account::factory()
        ->create([
            'coa_version_id' => $coa->id,
            'code' => $code,
            'name' => $name,
            'type' => $type,
            'normal_balance' => $normal,
            'is_active' => true,
        ]);

    $this->accounts = [
        'ar' => $make('11200', 'Accounts Receivable', 'asset', 'debit'),
        'ap' => $make('21100', 'Accounts Payable', 'liability', 'credit'),
        'bank' => $make('11110', 'Bank', 'asset', 'debit'),
        'revenue' => $make('41000', 'Sales Revenue', 'revenue', 'credit'),
        'expense' => $make('51100', 'Operating Expense', 'expense', 'debit'),
    ];
    $this->customer = Customer::factory()->create();
    $this->supplier = Supplier::factory()->create();
    $this->branch = Branch::factory()->create();
    $this->fiscalYear = FiscalYear::query()->where('name', '2026')->firstOrFail();
});

/**
 * Invoice terposting 1.000.000 dengan alokasi receipt confirmed 400.000.
 */
function settledInvoice(): CustomerInvoice
{
    $invoice = CustomerInvoice::factory()->create([
        'customer_id' => test()->customer->id,
        'branch_id' => test()->branch->id,
        'fiscal_year_id' => test()->fiscalYear->id,
        'invoice_date' => '2026-03-01',
        'status' => 'sent',
        'subtotal' => 1000000,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'grand_total' => 1000000,
        'amount_received' => 0,
        'credit_note_amount' => 0,
        'amount_due' => 1000000,
    ]);

    $invoice->items()->create([
        'account_id' => test()->accounts['revenue']->id,
        'description' => 'Jasa',
        'quantity' => 1,
        'unit_price' => 1000000,
        'discount_percent' => 0,
        'tax_percent' => 0,
        'line_total' => 1000000,
    ]);

    app(PostCustomerInvoiceJournalAction::class)->execute($invoice->fresh());

    return $invoice->fresh();
}

/**
 * Receipt confirmed yang mengalokasikan $amount ke invoice, lalu jurnalnya diposting.
 */
function confirmedReceipt(CustomerInvoice $invoice, float $amount, string $number): ArReceipt
{
    $receipt = ArReceipt::factory()->create([
        'receipt_number' => $number,
        'customer_id' => test()->customer->id,
        'branch_id' => test()->branch->id,
        'fiscal_year_id' => test()->fiscalYear->id,
        'bank_account_id' => test()->accounts['bank']->id,
        'receipt_date' => '2026-03-10',
        'status' => 'confirmed',
        'total_amount' => $amount,
        'total_allocated' => $amount,
        'total_unallocated' => 0,
    ]);

    ArReceiptAllocation::factory()->create([
        'ar_receipt_id' => $receipt->id,
        'customer_invoice_id' => $invoice->id,
        'allocated_amount' => $amount,
    ]);

    app(SyncArReceiptAllocationsAction::class)->refreshInvoices([$invoice->id]);

    app(PostArReceiptJournalAction::class)->execute($receipt->fresh());

    return $receipt->fresh();
}

test('batal receipt confirmed melepas alokasi, menghitung ulang sisa invoice, dan membalik jurnal', function () {
    $invoice = settledInvoice();
    $receipt = confirmedReceipt($invoice, 400000, 'RCV-2026-000375');

    $original = JournalEntry::query()
        ->where('source_type', ArReceipt::class)
        ->where('source_id', $receipt->id)
        ->firstOrFail();

    putJson("/api/ar-receipts/{$receipt->id}", ['status' => 'cancelled'])->assertOk();

    $invoice->refresh();
    expect((float) $invoice->amount_received)->toBe(0.0)
        ->and((float) $invoice->amount_due)->toBe(1000000.0)
        ->and($invoice->status)->toBe('sent');

    expect(ArReceiptAllocation::query()->where('ar_receipt_id', $receipt->id)->count())->toBe(0);

    $reversal = JournalEntry::query()
        ->where('source_type', ArReceipt::class)
        ->where('source_id', $receipt->id)
        ->where('id', '!=', $original->id)
        ->first();

    expect($reversal)->not->toBeNull()
        ->and(JournalEntry::find($original->id)->status)->toBe('posted')
        ->and(lineNetCreditOf($reversal, test()->accounts['bank']->id))->toBe(400000.0)
        ->and(lineNetCreditOf($reversal, test()->accounts['ar']->id))->toBe(-400000.0);

    assertDatabaseHas('ar_receipts', ['id' => $receipt->id, 'status' => 'cancelled']);
});

test('batal receipt dua kali diterima 200 tanpa efek: tidak ada pembalik kedua dan alokasi tetap nol', function () {
    $invoice = settledInvoice();
    $receipt = confirmedReceipt($invoice, 400000, 'RCV-2026-000376');

    putJson("/api/ar-receipts/{$receipt->id}", ['status' => 'cancelled'])->assertOk();
    $invoiceAfterFirst = $invoice->refresh()->only(['amount_received', 'amount_due', 'status']);

    putJson("/api/ar-receipts/{$receipt->id}", ['status' => 'cancelled'])->assertOk();

    expect(JournalEntry::query()
        ->where('source_type', ArReceipt::class)
        ->where('source_id', $receipt->id)
        ->count())->toBe(2)
        ->and($receipt->refresh()->status)->toBe('cancelled')
        ->and(ArReceiptAllocation::query()->where('ar_receipt_id', $receipt->id)->count())->toBe(0)
        ->and($invoice->refresh()->only(['amount_received', 'amount_due', 'status']))->toBe($invoiceAfterFirst);
});

test('batal receipt ditolak 422 bila periode jurnal sudah tertutup, alokasi tetap utuh', function () {
    $invoice = settledInvoice();
    $receipt = confirmedReceipt($invoice, 400000, 'RCV-2026-000377');

    FiscalYear::query()->update(['status' => 'closed']);

    putJson("/api/ar-receipts/{$receipt->id}", ['status' => 'cancelled'])->assertStatus(422);

    expect($receipt->refresh()->status)->toBe('confirmed')
        ->and(ArReceiptAllocation::query()->where('ar_receipt_id', $receipt->id)->count())->toBe(1);

    expect((float) $invoice->refresh()->amount_received)->toBe(400000.0);
});

/**
 * Bill terposting 800.000 dengan alokasi payment confirmed 300.000.
 */
function settledBill(): SupplierBill
{
    $bill = SupplierBill::factory()->create([
        'supplier_id' => test()->supplier->id,
        'branch_id' => test()->branch->id,
        'fiscal_year_id' => test()->fiscalYear->id,
        'bill_date' => '2026-03-01',
        'status' => 'confirmed',
        'subtotal' => 800000,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'grand_total' => 800000,
        'amount_paid' => 0,
        'amount_due' => 800000,
    ]);

    $bill->items()->create([
        'account_id' => test()->accounts['expense']->id,
        'description' => 'Biaya',
        'quantity' => 1,
        'unit_price' => 800000,
        'discount_percent' => 0,
        'tax_percent' => 0,
        'line_total' => 800000,
    ]);

    app(PostSupplierBillJournalAction::class)->execute($bill->fresh());

    return $bill->fresh();
}

/**
 * Payment confirmed yang mengalokasikan $amount ke bill, lalu jurnalnya diposting.
 */
function confirmedPayment(SupplierBill $bill, float $amount, string $number): ApPayment
{
    $payment = ApPayment::factory()->create([
        'payment_number' => $number,
        'supplier_id' => test()->supplier->id,
        'branch_id' => test()->branch->id,
        'fiscal_year_id' => test()->fiscalYear->id,
        'bank_account_id' => test()->accounts['bank']->id,
        'payment_date' => '2026-03-10',
        'status' => 'confirmed',
        'total_amount' => $amount,
        'total_allocated' => $amount,
        'total_unallocated' => 0,
    ]);

    ApPaymentAllocation::factory()->create([
        'ap_payment_id' => $payment->id,
        'supplier_bill_id' => $bill->id,
        'allocated_amount' => $amount,
    ]);

    app(SyncApPaymentAllocationsAction::class)->refreshBills([$bill->id]);

    app(PostApPaymentJournalAction::class)->execute($payment->fresh());

    return $payment->fresh();
}

test('batal payment confirmed melepas alokasi, menghitung ulang sisa bill, dan membalik jurnal', function () {
    $bill = settledBill();
    $payment = confirmedPayment($bill, 300000, 'PAY-2026-000375');

    $original = JournalEntry::query()
        ->where('source_type', ApPayment::class)
        ->where('source_id', $payment->id)
        ->firstOrFail();

    putJson("/api/ap-payments/{$payment->id}", ['status' => 'cancelled'])->assertOk();

    $bill->refresh();
    expect((float) $bill->amount_paid)->toBe(0.0)
        ->and((float) $bill->amount_due)->toBe(800000.0)
        ->and($bill->status)->toBe('confirmed');

    expect(ApPaymentAllocation::query()->where('ap_payment_id', $payment->id)->count())->toBe(0);

    $reversal = JournalEntry::query()
        ->where('source_type', ApPayment::class)
        ->where('source_id', $payment->id)
        ->where('id', '!=', $original->id)
        ->first();

    expect($reversal)->not->toBeNull()
        ->and(JournalEntry::find($original->id)->status)->toBe('posted')
        ->and(lineNetCreditOf($reversal, test()->accounts['bank']->id))->toBe(-300000.0)
        ->and(lineNetCreditOf($reversal, test()->accounts['ap']->id))->toBe(300000.0);

    assertDatabaseHas('ap_payments', ['id' => $payment->id, 'status' => 'cancelled']);
});

test('batal payment dua kali diterima 200 tanpa efek: tidak ada pembalik kedua dan alokasi tetap nol', function () {
    $bill = settledBill();
    $payment = confirmedPayment($bill, 300000, 'PAY-2026-000376');

    putJson("/api/ap-payments/{$payment->id}", ['status' => 'cancelled'])->assertOk();
    $billAfterFirst = $bill->refresh()->only(['amount_paid', 'amount_due', 'status']);

    putJson("/api/ap-payments/{$payment->id}", ['status' => 'cancelled'])->assertOk();

    expect(JournalEntry::query()
        ->where('source_type', ApPayment::class)
        ->where('source_id', $payment->id)
        ->count())->toBe(2)
        ->and($payment->refresh()->status)->toBe('cancelled')
        ->and(ApPaymentAllocation::query()->where('ap_payment_id', $payment->id)->count())->toBe(0)
        ->and($bill->refresh()->only(['amount_paid', 'amount_due', 'status']))->toBe($billAfterFirst);
});

test('batal payment ditolak 422 bila periode jurnal sudah tertutup, alokasi tetap utuh', function () {
    $bill = settledBill();
    $payment = confirmedPayment($bill, 300000, 'PAY-2026-000377');

    FiscalYear::query()->update(['status' => 'closed']);

    putJson("/api/ap-payments/{$payment->id}", ['status' => 'cancelled'])->assertStatus(422);

    expect($payment->refresh()->status)->toBe('confirmed')
        ->and(ApPaymentAllocation::query()->where('ap_payment_id', $payment->id)->count())->toBe(1)
        ->and((float) $bill->refresh()->amount_paid)->toBe(300000.0);
});

/**
 * Credit note applied 200.000 terhadap invoice terposting 1.000.000.
 */
function appliedCreditNote(CustomerInvoice $invoice, float $amount, string $number): CreditNote
{
    $creditNote = CreditNote::factory()->create([
        'credit_note_number' => $number,
        'customer_id' => test()->customer->id,
        'customer_invoice_id' => $invoice->id,
        'branch_id' => test()->branch->id,
        'fiscal_year_id' => test()->fiscalYear->id,
        'credit_note_date' => '2026-03-12',
        'status' => 'confirmed',
        'subtotal' => $amount,
        'tax_amount' => 0,
        'grand_total' => $amount,
    ]);

    $creditNote->items()->create([
        'account_id' => test()->accounts['revenue']->id,
        'description' => 'Retur jasa',
        'quantity' => 1,
        'unit_price' => $amount,
        'discount_percent' => 0,
        'tax_percent' => 0,
        'line_total' => $amount,
    ]);

    app(ApplyCreditNoteAction::class)->execute($creditNote->fresh());

    return $creditNote->fresh();
}

test('batal credit note applied membalik jurnal dan mengembalikan sisa invoice', function () {
    $invoice = settledInvoice();
    $creditNote = appliedCreditNote($invoice, 200000, 'CN-2026-000375');

    expect((float) $invoice->refresh()->credit_note_amount)->toBe(200000.0);

    putJson("/api/credit-notes/{$creditNote->id}", ['status' => 'cancelled'])->assertOk();

    $invoice->refresh();
    expect((float) $invoice->credit_note_amount)->toBe(0.0)
        ->and((float) $invoice->amount_due)->toBe(1000000.0)
        ->and($invoice->status)->toBe('sent');

    $original = JournalEntry::query()
        ->where('source_type', CreditNote::class)
        ->where('source_id', $creditNote->id)
        ->orderBy('id')
        ->firstOrFail();
    $reversal = JournalEntry::query()
        ->where('source_type', CreditNote::class)
        ->where('source_id', $creditNote->id)
        ->where('id', '!=', $original->id)
        ->first();

    expect($reversal)->not->toBeNull()
        ->and(JournalEntry::find($original->id)->status)->toBe('posted')
        ->and(lineNetCreditOf($reversal, test()->accounts['revenue']->id))->toBe(200000.0);

    assertDatabaseHas('credit_notes', ['id' => $creditNote->id, 'status' => 'cancelled']);
});

test('batal credit note confirmed yang belum applied tidak mengurangi credit note lain', function () {
    $invoice = settledInvoice();
    appliedCreditNote($invoice, 200000, 'CN-2026-000385');
    $before = $invoice->refresh()->only(['credit_note_amount', 'amount_due', 'status']);

    $unapplied = CreditNote::factory()->create([
        'credit_note_number' => 'CN-2026-000386',
        'customer_id' => $invoice->customer_id,
        'customer_invoice_id' => $invoice->id,
        'grand_total' => 50000,
        'status' => 'confirmed',
        'journal_entry_id' => null,
    ]);

    putJson("/api/credit-notes/{$unapplied->id}", ['status' => 'cancelled'])->assertOk();

    expect($invoice->refresh()->only(['credit_note_amount', 'amount_due', 'status']))->toBe($before);
});

test('batal credit note applied dua kali diterima 200 tanpa efek: tidak ada pembalik kedua', function () {
    $invoice = settledInvoice();
    $creditNote = appliedCreditNote($invoice, 200000, 'CN-2026-000376');

    putJson("/api/credit-notes/{$creditNote->id}", ['status' => 'cancelled'])->assertOk();
    $invoiceAfterFirst = $invoice->refresh()->only(['credit_note_amount', 'amount_due', 'status']);

    putJson("/api/credit-notes/{$creditNote->id}", ['status' => 'cancelled'])->assertOk();

    expect(JournalEntry::query()
        ->where('source_type', CreditNote::class)
        ->where('source_id', $creditNote->id)
        ->count())->toBe(2)
        ->and($creditNote->refresh()->status)->toBe('cancelled')
        ->and($invoice->refresh()->only(['credit_note_amount', 'amount_due', 'status']))->toBe($invoiceAfterFirst);
});

/**
 * @return float net kredit (kredit - debit) baris untuk akun pada jurnal
 */
function lineNetCreditOf(JournalEntry $entry, int $accountId): float
{
    return round(
        JournalEntryLine::query()
            ->where('journal_entry_id', $entry->id)
            ->where('account_id', $accountId)
            ->get(['debit', 'credit'])
            ->sum(fn ($line) => (float) $line->credit - (float) $line->debit),
        2,
    );
}
