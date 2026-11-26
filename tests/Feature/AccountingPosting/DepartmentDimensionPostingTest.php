<?php

use App\Actions\AccountingPosting\PostCreditNoteJournalAction;
use App\Actions\AccountingPosting\PostCustomerInvoiceJournalAction;
use App\Actions\AccountingPosting\PostSupplierBillJournalAction;
use App\Actions\AccountingPosting\ReverseDocumentJournalAction;
use App\Models\Account;
use App\Models\Branch;
use App\Models\CoaVersion;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\Department;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Supplier;
use App\Models\SupplierBill;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('accounting-posting');

/**
 * Dimensi departemen pada posting (#114 fase 1): hanya baris akun pendapatan/beban
 * yang membawa department_id; AR/AP/pajak/GR-IR tetap null.
 */
function deptPostingFixture(): array
{
    $fiscalYear = FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);

    /** @var CoaVersion $coa */
    $coa = CoaVersion::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'active',
    ]);

    $account = fn (string $code, string $name, string $type, string $normal) => Account::factory()->create([
        'coa_version_id' => $coa->id,
        'code' => $code,
        'name' => $name,
        'type' => $type,
        'normal_balance' => $normal,
        'is_active' => true,
    ]);

    return [
        'fiscal_year' => $fiscalYear,
        'branch' => Branch::factory()->create(),
        'ar' => $account('11200', 'Accounts Receivable', 'asset', 'debit'),
        'ap' => $account('21100', 'Accounts Payable', 'liability', 'credit'),
        'revenue' => $account('41000', 'Sales Revenue', 'revenue', 'credit'),
        'expense' => $account('52000', 'Operating Expense', 'expense', 'debit'),
        'price_variance' => $account('51000', 'Purchase Price Variance', 'expense', 'debit'),
        'department' => Department::factory()->create(),
    ];
}

function deptLinesByAccount(JournalEntry $entry): array
{
    return JournalEntryLine::query()
        ->where('journal_entry_id', $entry->id)
        ->get()
        ->keyBy('account_id')
        ->all();
}

describe('Posting customer invoice dengan departemen', function () {
    test('baris pendapatan mewarisi departemen, baris AR tetap null', function () {
        $f = deptPostingFixture();

        /** @var CustomerInvoice $invoice */
        $invoice = CustomerInvoice::factory()->sent()->create([
            'customer_id' => Customer::factory(),
            'branch_id' => $f['branch']->id,
            'fiscal_year_id' => $f['fiscal_year']->id,
            'department_id' => $f['department']->id,
            'invoice_date' => '2026-03-15',
            'invoice_number' => 'INV-2026-000001',
            'grand_total' => 1000,
            'amount_due' => 1000,
            'journal_entry_id' => null,
        ]);
        $invoice->items()->create([
            'account_id' => $f['revenue']->id,
            'description' => 'Jasa',
            'quantity' => 1,
            'unit_price' => 1000,
            'discount_percent' => 0,
            'tax_percent' => 0,
            'line_total' => 1000,
        ]);

        $entry = app(PostCustomerInvoiceJournalAction::class)->execute($invoice->fresh());
        $lines = deptLinesByAccount($entry);

        expect($lines[$f['revenue']->id]->department_id)->toBe($f['department']->id)
            ->and($lines[$f['ar']->id]->department_id)->toBeNull();
    });

    test('tanpa departemen semua baris tetap null (jurnal identik dengan sebelumnya)', function () {
        $f = deptPostingFixture();

        /** @var CustomerInvoice $invoice */
        $invoice = CustomerInvoice::factory()->sent()->create([
            'customer_id' => Customer::factory(),
            'branch_id' => $f['branch']->id,
            'fiscal_year_id' => $f['fiscal_year']->id,
            'department_id' => null,
            'invoice_date' => '2026-03-15',
            'invoice_number' => 'INV-2026-000002',
            'grand_total' => 1000,
            'amount_due' => 1000,
            'journal_entry_id' => null,
        ]);
        $invoice->items()->create([
            'account_id' => $f['revenue']->id,
            'description' => 'Jasa',
            'quantity' => 1,
            'unit_price' => 1000,
            'discount_percent' => 0,
            'tax_percent' => 0,
            'line_total' => 1000,
        ]);

        $entry = app(PostCustomerInvoiceJournalAction::class)->execute($invoice->fresh());

        expect(JournalEntryLine::query()->where('journal_entry_id', $entry->id)->whereNotNull('department_id')->exists())
            ->toBeFalse()
            ->and((float) $entry->total_debit)->toBe(1000.0)
            ->and((float) $entry->total_credit)->toBe(1000.0);
    });
});

describe('Posting supplier bill dengan departemen', function () {
    test('baris beban mewarisi departemen, baris AP tetap null', function () {
        $f = deptPostingFixture();

        /** @var SupplierBill $bill */
        $bill = SupplierBill::factory()->confirmed()->create([
            'supplier_id' => Supplier::factory(),
            'branch_id' => $f['branch']->id,
            'fiscal_year_id' => $f['fiscal_year']->id,
            'department_id' => $f['department']->id,
            'bill_number' => 'BILL-2026-000101',
            'bill_date' => '2026-03-15',
            'grand_total' => 500,
            'amount_due' => 500,
        ]);
        $bill->items()->create([
            'account_id' => $f['expense']->id,
            'description' => 'Biaya',
            'quantity' => 1,
            'unit_price' => 500,
            'discount_percent' => 0,
            'tax_percent' => 0,
            'line_total' => 500,
        ]);

        $entry = app(PostSupplierBillJournalAction::class)->execute($bill->fresh());
        $lines = deptLinesByAccount($entry);

        expect($lines[$f['expense']->id]->department_id)->toBe($f['department']->id)
            ->and($lines[$f['ap']->id]->department_id)->toBeNull();
    });

    test('tanpa departemen semua baris tetap null', function () {
        $f = deptPostingFixture();

        /** @var SupplierBill $bill */
        $bill = SupplierBill::factory()->confirmed()->create([
            'supplier_id' => Supplier::factory(),
            'branch_id' => $f['branch']->id,
            'fiscal_year_id' => $f['fiscal_year']->id,
            'department_id' => null,
            'bill_number' => 'BILL-2026-000102',
            'bill_date' => '2026-03-15',
            'grand_total' => 500,
            'amount_due' => 500,
        ]);
        $bill->items()->create([
            'account_id' => $f['expense']->id,
            'description' => 'Biaya',
            'quantity' => 1,
            'unit_price' => 500,
            'discount_percent' => 0,
            'tax_percent' => 0,
            'line_total' => 500,
        ]);

        $entry = app(PostSupplierBillJournalAction::class)->execute($bill->fresh());

        expect(JournalEntryLine::query()->where('journal_entry_id', $entry->id)->whereNotNull('department_id')->exists())
            ->toBeFalse();
    });
});

describe('Posting credit note dengan departemen', function () {
    test('memakai departemen credit note bila diisi', function () {
        $f = deptPostingFixture();
        $other = Department::factory()->create();

        /** @var CreditNote $note */
        $note = CreditNote::factory()->confirmed()->create([
            'customer_id' => Customer::factory(),
            'branch_id' => $f['branch']->id,
            'fiscal_year_id' => $f['fiscal_year']->id,
            'department_id' => $other->id,
            'credit_note_date' => '2026-03-20',
            'credit_note_number' => 'CN-2026-000001',
            'journal_entry_id' => null,
        ]);
        $note->items()->create([
            'account_id' => $f['revenue']->id,
            'description' => 'Retur',
            'quantity' => 1,
            'unit_price' => 300,
            'discount_percent' => 0,
            'tax_percent' => 0,
            'line_total' => 300,
        ]);

        $entry = app(PostCreditNoteJournalAction::class)->execute($note->fresh());
        $lines = deptLinesByAccount($entry);

        expect($lines[$f['revenue']->id]->department_id)->toBe($other->id)
            ->and($lines[$f['ar']->id]->department_id)->toBeNull();
    });

    test('bila kosong, mewarisi departemen dari invoice terkait', function () {
        $f = deptPostingFixture();

        /** @var CustomerInvoice $invoice */
        $invoice = CustomerInvoice::factory()->sent()->create([
            'customer_id' => Customer::factory(),
            'branch_id' => $f['branch']->id,
            'fiscal_year_id' => $f['fiscal_year']->id,
            'department_id' => $f['department']->id,
            'invoice_date' => '2026-03-15',
        ]);

        /** @var CreditNote $note */
        $note = CreditNote::factory()->confirmed()->create([
            'customer_id' => Customer::factory(),
            'customer_invoice_id' => $invoice->id,
            'branch_id' => $f['branch']->id,
            'fiscal_year_id' => $f['fiscal_year']->id,
            'department_id' => null,
            'credit_note_date' => '2026-03-20',
            'credit_note_number' => 'CN-2026-000002',
            'journal_entry_id' => null,
        ]);
        $note->items()->create([
            'account_id' => $f['revenue']->id,
            'description' => 'Retur',
            'quantity' => 1,
            'unit_price' => 300,
            'discount_percent' => 0,
            'tax_percent' => 0,
            'line_total' => 300,
        ]);

        $entry = app(PostCreditNoteJournalAction::class)->execute($note->fresh());
        $lines = deptLinesByAccount($entry);

        expect($lines[$f['revenue']->id]->department_id)->toBe($f['department']->id);
    });

    test('tanpa departemen dan tanpa invoice terkait, semua baris null', function () {
        $f = deptPostingFixture();

        /** @var CreditNote $note */
        $note = CreditNote::factory()->confirmed()->create([
            'customer_id' => Customer::factory(),
            'customer_invoice_id' => null,
            'branch_id' => $f['branch']->id,
            'fiscal_year_id' => $f['fiscal_year']->id,
            'department_id' => null,
            'credit_note_date' => '2026-03-20',
            'credit_note_number' => 'CN-2026-000003',
            'journal_entry_id' => null,
        ]);
        $note->items()->create([
            'account_id' => $f['revenue']->id,
            'description' => 'Retur',
            'quantity' => 1,
            'unit_price' => 300,
            'discount_percent' => 0,
            'tax_percent' => 0,
            'line_total' => 300,
        ]);

        $entry = app(PostCreditNoteJournalAction::class)->execute($note->fresh());

        expect(JournalEntryLine::query()->where('journal_entry_id', $entry->id)->whereNotNull('department_id')->exists())
            ->toBeFalse();
    });
});

describe('Reversal menyalin departemen baris asal', function () {
    test('baris pembalik membawa department_id yang sama dengan baris asal', function () {
        $f = deptPostingFixture();

        /** @var CustomerInvoice $invoice */
        $invoice = CustomerInvoice::factory()->sent()->create([
            'customer_id' => Customer::factory(),
            'branch_id' => $f['branch']->id,
            'fiscal_year_id' => $f['fiscal_year']->id,
            'department_id' => $f['department']->id,
            'invoice_date' => '2026-03-15',
            'invoice_number' => 'INV-2026-000009',
            'grand_total' => 1000,
            'amount_due' => 1000,
            'journal_entry_id' => null,
        ]);
        $invoice->items()->create([
            'account_id' => $f['revenue']->id,
            'description' => 'Jasa',
            'quantity' => 1,
            'unit_price' => 1000,
            'discount_percent' => 0,
            'tax_percent' => 0,
            'line_total' => 1000,
        ]);

        $original = app(PostCustomerInvoiceJournalAction::class)->execute($invoice->fresh());

        $reversal = app(ReverseDocumentJournalAction::class)->execute(
            $invoice->fresh(),
            'INV-2026-000009',
            'Invoice',
            '2026-03-31',
            null,
            $original->id,
        );

        $reversalLines = deptLinesByAccount($reversal);

        expect($reversalLines[$f['revenue']->id]->department_id)->toBe($f['department']->id)
            ->and($reversalLines[$f['ar']->id]->department_id)->toBeNull();
    });
});
