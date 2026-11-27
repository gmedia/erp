<?php

use App\Actions\AccountingPosting\PostCreditNoteJournalAction;
use App\Actions\AccountingPosting\PostCustomerInvoiceJournalAction;
use App\Actions\AccountingPosting\PostSupplierBillJournalAction;
use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\CreditNote;
use App\Models\CustomerInvoice;
use App\Models\Department;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\SupplierBill;
use App\Support\Accounting\DepartmentPostingRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('department-dimension');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'journal_entry',
        'journal_entry.create',
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
 * Akun dengan tipe eksplisit untuk satu COA aktif (#563).
 *
 * @return array{ar: Account, ap: Account, revenue: Account, expense: Account, asset: Account}
 */
function deptDimensionAccounts(): array
{
    $coa = CoaVersion::factory()->create(['status' => 'active']);

    $make = static fn (string $code, string $name, string $type, string $normal): Account => Account::factory()->create([
        'coa_version_id' => $coa->id,
        'code' => $code,
        'name' => $name,
        'type' => $type,
        'normal_balance' => $normal,
        'is_active' => true,
    ]);

    return [
        'ar' => $make('11200', 'Accounts Receivable', 'asset', 'debit'),
        'ap' => $make('21100', 'Accounts Payable', 'liability', 'credit'),
        'revenue' => $make('41000', 'Sales Revenue', 'revenue', 'credit'),
        'expense' => $make('51100', 'Operating Expense', 'expense', 'debit'),
        'asset' => $make('11120', 'Peralatan Kantor', 'asset', 'debit'),
    ];
}

/**
 * Baris jurnal posting, dipetakan account_id => [department_id, debit, credit].
 *
 * @return array<int, array{department_id: int|null, debit: float, credit: float}>
 */
function deptDimensionLinesOf(JournalEntry $entry): array
{
    return JournalEntryLine::query()
        ->where('journal_entry_id', $entry->id)
        ->get()
        ->mapWithKeys(fn (JournalEntryLine $line): array => [
            (int) $line->account_id => [
                'department_id' => $line->department_id,
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
            ],
        ])
        ->all();
}

test('posting supplier bill: departemen hanya ke baris beban, baris aset tanpa departemen', function () {
    $accounts = deptDimensionAccounts();
    $department = Department::factory()->create();

    $bill = SupplierBill::factory()->create([
        'bill_number' => 'BILL-563-0001',
        'bill_date' => '2026-03-15',
        'status' => 'confirmed',
        'department_id' => $department->id,
        'subtotal' => 1000000,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'grand_total' => 1000000,
        'amount_paid' => 0,
        'amount_due' => 1000000,
    ]);
    $bill->items()->create([
        'account_id' => $accounts['expense']->id,
        'description' => 'Biaya operasional',
        'quantity' => 1,
        'unit_price' => 600000,
        'discount_percent' => 0,
        'tax_percent' => 0,
        'line_total' => 600000,
    ]);
    $bill->items()->create([
        'account_id' => $accounts['asset']->id,
        'description' => 'Pembelian meja',
        'quantity' => 1,
        'unit_price' => 400000,
        'discount_percent' => 0,
        'tax_percent' => 0,
        'line_total' => 400000,
    ]);

    $entry = app(PostSupplierBillJournalAction::class)->execute($bill->fresh());
    $lines = deptDimensionLinesOf($entry);

    expect($lines[$accounts['expense']->id]['department_id'])->toBe($department->id)
        ->and($lines[$accounts['asset']->id]['department_id'])->toBeNull()
        ->and($lines[$accounts['asset']->id]['debit'])->toBe(400000.0)
        ->and($lines[$accounts['ap']->id]['department_id'])->toBeNull();
});

test('posting customer invoice: departemen hanya ke baris pendapatan, baris non-pendapatan tanpa departemen', function () {
    $accounts = deptDimensionAccounts();
    $department = Department::factory()->create();

    $invoice = CustomerInvoice::factory()->create([
        'invoice_number' => 'INV-563-0001',
        'invoice_date' => '2026-03-15',
        'status' => 'sent',
        'department_id' => $department->id,
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

    $entry = app(PostCustomerInvoiceJournalAction::class)->execute($invoice->fresh());
    $lines = deptDimensionLinesOf($entry);

    expect($lines[$accounts['revenue']->id]['department_id'])->toBe($department->id)
        ->and($lines[$accounts['ar']->id]['department_id'])->toBeNull();
});

test('posting credit note: departemen hanya ke baris pendapatan, baris aset tanpa departemen', function () {
    $accounts = deptDimensionAccounts();
    $department = Department::factory()->create();

    $creditNote = CreditNote::factory()->create([
        'credit_note_number' => 'CN-563-0001',
        'credit_note_date' => '2026-03-20',
        'status' => 'confirmed',
        'department_id' => $department->id,
        'subtotal' => 300000,
        'tax_amount' => 0,
        'grand_total' => 300000,
    ]);
    $creditNote->items()->create([
        'account_id' => $accounts['revenue']->id,
        'description' => 'Retur jasa',
        'quantity' => 1,
        'unit_price' => 200000,
        'discount_percent' => 0,
        'tax_percent' => 0,
        'line_total' => 200000,
    ]);
    $creditNote->items()->create([
        'account_id' => $accounts['asset']->id,
        'description' => 'Retur aset',
        'quantity' => 1,
        'unit_price' => 100000,
        'discount_percent' => 0,
        'tax_percent' => 0,
        'line_total' => 100000,
    ]);

    $entry = app(PostCreditNoteJournalAction::class)->execute($creditNote->fresh());
    $lines = deptDimensionLinesOf($entry);

    expect($lines[$accounts['revenue']->id]['department_id'])->toBe($department->id)
        ->and($lines[$accounts['asset']->id]['department_id'])->toBeNull()
        ->and($lines[$accounts['ar']->id]['department_id'])->toBeNull();
});

test('jurnal manual: departemen pada akun aset ditolak 422 dengan pesan terjemahan', function () {
    $accounts = deptDimensionAccounts();
    $department = Department::factory()->create();

    postJson('/api/journal-entries', [
        'entry_date' => '2026-03-15',
        'description' => 'Pembelian aset dengan departemen',
        'lines' => [
            [
                'account_id' => $accounts['asset']->id,
                'department_id' => $department->id,
                'debit' => 250000,
                'credit' => 0,
            ],
            [
                'account_id' => $accounts['ap']->id,
                'debit' => 0,
                'credit' => 250000,
            ],
        ],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['lines.0.department_id']);

    expect(JournalEntry::query()->where('description', 'Pembelian aset dengan departemen')->exists())->toBeFalse();
});

test('jurnal manual: departemen pada akun beban diterima', function () {
    $accounts = deptDimensionAccounts();
    $department = Department::factory()->create();

    postJson('/api/journal-entries', [
        'entry_date' => '2026-03-15',
        'description' => 'Beban dengan departemen',
        'lines' => [
            [
                'account_id' => $accounts['expense']->id,
                'department_id' => $department->id,
                'debit' => 250000,
                'credit' => 0,
            ],
            [
                'account_id' => $accounts['ap']->id,
                'debit' => 0,
                'credit' => 250000,
            ],
        ],
    ])->assertCreated();
});

test('helper: departemen efektif hanya untuk pendapatan dan beban', function () {
    expect(DepartmentPostingRule::resolve(7, 'revenue'))->toBe(7)
        ->and(DepartmentPostingRule::resolve(7, 'expense'))->toBe(7)
        ->and(DepartmentPostingRule::resolve(7, 'asset'))->toBeNull()
        ->and(DepartmentPostingRule::resolve(7, 'liability'))->toBeNull()
        ->and(DepartmentPostingRule::resolve(7, null))->toBeNull();
});
