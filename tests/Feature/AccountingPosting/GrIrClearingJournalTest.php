<?php

use App\Actions\AccountingPosting\PostGoodsReceiptJournalAction;
use App\Actions\AccountingPosting\PostSupplierBillJournalAction;
use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\SupplierBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class)->group('gr-ir-clearing');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'goods_receipt',
        'goods_receipt.edit',
        'supplier_bill',
        'supplier_bill.edit',
    ]);
    Sanctum::actingAs($user, ['*']);
});

const GRIR_TEST_AMOUNT = 500000.0;

function makeGrIrFiscalYear(): FiscalYear
{
    return FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);
}

/**
 * @return array{inventory: Account, ap: Account, grir: Account, expense: Account}
 */
function makeGrIrAccounts(): array
{
    $fiscalYear = FiscalYear::where('status', 'open')->firstOrFail();
    $coaVersion = CoaVersion::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'active',
    ]);

    $make = static fn (string $code, string $name, string $type, string $normal): Account => Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => $code,
        'name' => $name,
        'type' => $type,
        'normal_balance' => $normal,
        'is_active' => true,
    ]);

    return [
        'inventory' => $make('11300', 'Inventory', 'asset', 'debit'),
        'ap' => $make('21100', 'Accounts Payable', 'liability', 'credit'),
        'grir' => $make('21150', 'Goods Received Not Invoiced', 'liability', 'credit'),
        'expense' => $make('52000', 'General & Admin Expense', 'expense', 'debit'),
    ];
}

function confirmedGrOfAmount(float $amount, string $number): GoodsReceipt
{
    $gr = GoodsReceipt::factory()->create([
        'status' => 'confirmed',
        'gr_number' => $number,
        'receipt_date' => '2026-03-15',
        'journal_entry_id' => null,
        'confirmed_at' => now(),
    ]);

    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr->id,
        'quantity_received' => 1,
        'quantity_accepted' => 1,
        'quantity_rejected' => 0,
        'unit_price' => $amount,
    ]);

    return $gr->fresh(['items']);
}

/**
 * Bill atas GR. Item bill memakai akun beban; akun yang didebit bill atas GR
 * harus berasal dari jurnal GR (akun yang dikreditnya), bukan dari akun item.
 */
function billOfGr(GoodsReceipt $gr, int $expenseAccountId, float $amount, string $number): SupplierBill
{
    $bill = SupplierBill::factory()->confirmed()->create([
        'goods_receipt_id' => $gr->id,
        'bill_number' => $number,
        'bill_date' => '2026-03-20',
        'subtotal' => $amount,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'grand_total' => $amount,
        'amount_paid' => 0,
        'amount_due' => $amount,
    ]);

    $bill->items()->create([
        'account_id' => $expenseAccountId,
        'goods_receipt_item_id' => $gr->items->first()->id,
        'description' => 'Pembelian sesuai GR',
        'quantity' => 1,
        'unit_price' => $amount,
        'discount_percent' => 0,
        'tax_percent' => 0,
        'line_total' => $amount,
    ]);

    return $bill->fresh(['items']);
}

/** Saldo kredit bersih (kredit - debit) satu akun pada satu jurnal. */
function lineNetCredit(JournalEntry $entry, int $accountId): float
{
    $lines = JournalEntryLine::query()
        ->where('journal_entry_id', $entry->id)
        ->where('account_id', $accountId)
        ->get(['debit', 'credit']);

    return round($lines->sum(fn ($l) => (float) $l->credit - (float) $l->debit), 2);
}

/** Saldo kredit bersih satu akun dari seluruh jurnal berstatus posted. */
function postedNetCredit(int $accountId): float
{
    $lines = JournalEntryLine::query()
        ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
        ->where('journal_entries.status', 'posted')
        ->where('journal_entry_lines.account_id', $accountId)
        ->get(['journal_entry_lines.debit', 'journal_entry_lines.credit']);

    return round($lines->sum(fn ($l) => (float) $l->credit - (float) $l->debit), 2);
}

test('GR baru mengkredit GR/IR; bill atas GR mendebit GR/IR; saldo GR/IR nol dan 21100 hanya sekali', function () {
    makeGrIrFiscalYear();
    $accounts = makeGrIrAccounts();

    $gr = confirmedGrOfAmount(GRIR_TEST_AMOUNT, 'GR-2026-000101');
    $grEntry = app(PostGoodsReceiptJournalAction::class)->execute($gr);

    expect(lineNetCredit($grEntry, $accounts['grir']->id))->toBe(GRIR_TEST_AMOUNT)
        ->and(lineNetCredit($grEntry, $accounts['ap']->id))->toBe(0.0);

    $bill = billOfGr($gr, $accounts['expense']->id, GRIR_TEST_AMOUNT, 'BILL-2026-000101');
    $billEntry = app(PostSupplierBillJournalAction::class)->execute($bill);

    expect($billEntry->isBalanced())->toBeTrue()
        ->and(lineNetCredit($billEntry, $accounts['grir']->id))->toBe(-GRIR_TEST_AMOUNT)
        ->and(lineNetCredit($billEntry, $accounts['ap']->id))->toBe(GRIR_TEST_AMOUNT);

    expect(postedNetCredit($accounts['grir']->id))->toBe(0.0)
        ->and(postedNetCredit($accounts['ap']->id))->toBe(GRIR_TEST_AMOUNT);
});

test('GR lama yang mengkredit 21100 lalu bill: bill mendebit 21100 (akun yang dikredit GR), utang tidak ganda', function () {
    makeGrIrFiscalYear();
    $accounts = makeGrIrAccounts();

    // Jurnal GR lama: dibuat sebelum perubahan, mengkredit 21100 (AP).
    $gr = confirmedGrOfAmount(GRIR_TEST_AMOUNT, 'GR-2026-000102');
    $legacyEntry = app(CreateJournalEntryAction::class)->execute([
        'entry_date' => '2026-03-15',
        'reference' => $gr->gr_number,
        'description' => "Goods Receipt {$gr->gr_number}",
        'status' => 'posted',
        'journal_type' => 'system',
        'source_type' => GoodsReceipt::class,
        'source_id' => $gr->id,
        'branch_id' => null,
        'lines' => [
            ['account_id' => $accounts['inventory']->id, 'debit' => GRIR_TEST_AMOUNT, 'credit' => 0, 'memo' => 'GR'],
            ['account_id' => $accounts['ap']->id, 'debit' => 0, 'credit' => GRIR_TEST_AMOUNT, 'memo' => 'GR'],
        ],
    ]);
    $gr->forceFill(['journal_entry_id' => $legacyEntry->id])->save();

    $bill = billOfGr($gr, $accounts['expense']->id, GRIR_TEST_AMOUNT, 'BILL-2026-000102');
    $billEntry = app(PostSupplierBillJournalAction::class)->execute($bill);

    // Bill atas GR lama: sisi debit clearing dan sisi kredit AP sama-sama memakai 21100.
    // Bersih per akun nol, tetapi kedua sisi harus ada (tidak boleh hilang atau ganda).
    $apLines = JournalEntryLine::query()
        ->where('journal_entry_id', $billEntry->id)
        ->where('account_id', $accounts['ap']->id)
        ->get(['debit', 'credit']);

    expect($billEntry->isBalanced())->toBeTrue()
        ->and((float) $apLines->sum('debit'))->toBe(GRIR_TEST_AMOUNT)
        ->and((float) $apLines->sum('credit'))->toBe(GRIR_TEST_AMOUNT)
        ->and(lineNetCredit($billEntry, $accounts['grir']->id))->toBe(0.0);

    // Utang 21100 dari GR lama + bill = tepat satu kali nilai GR (bukan nol, bukan ganda).
    // GR lama mengkredit 21100 sekali; bill atas GR itu tidak menambah utang baru.
    expect(postedNetCredit($accounts['ap']->id))->toBe(GRIR_TEST_AMOUNT)
        ->and(JournalEntry::find($legacyEntry->id)->status)->toBe('posted');
});

test('bill tanpa GR tetap mendebit beban dan mengkredit 21100 seperti sebelumnya', function () {
    makeGrIrFiscalYear();
    $accounts = makeGrIrAccounts();

    $bill = SupplierBill::factory()->confirmed()->create([
        'goods_receipt_id' => null,
        'bill_number' => 'BILL-2026-000103',
        'bill_date' => '2026-03-20',
        'subtotal' => 250000,
        'grand_total' => 250000,
        'amount_paid' => 0,
        'amount_due' => 250000,
    ]);
    $bill->items()->create([
        'account_id' => $accounts['expense']->id,
        'description' => 'Jasa',
        'quantity' => 1,
        'unit_price' => 250000,
        'discount_percent' => 0,
        'tax_percent' => 0,
        'line_total' => 250000,
    ]);

    $entry = app(PostSupplierBillJournalAction::class)->execute($bill->fresh(['items']));

    expect(lineNetCredit($entry, $accounts['ap']->id))->toBe(250000.0)
        ->and(lineNetCredit($entry, $accounts['expense']->id))->toBe(-250000.0)
        ->and(lineNetCredit($entry, $accounts['grir']->id))->toBe(0.0);
});

