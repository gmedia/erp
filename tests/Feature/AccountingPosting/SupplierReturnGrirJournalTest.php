<?php

use App\Actions\AccountingPosting\PostGoodsReceiptJournalAction;
use App\Actions\AccountingPosting\PostSupplierBillJournalAction;
use App\Actions\AccountingPosting\PostSupplierReturnJournalAction;
use App\Actions\AccountingPosting\ReverseSupplierReturnJournalAction;
use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\SupplierBill;
use App\Models\SupplierReturn;
use App\Models\SupplierReturnItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('supplier-return-grir');

const SR_GRIR_UNIT_PRICE = 50000.0;

/**
 * Akun aktif untuk skenario retur: inventory 11300, AP 21100, GR/IR 21150, beban 52000.
 *
 * @return array{inventory: Account, ap: Account, grir: Account, expense: Account}
 */
function srGrirAccounts(): array
{
    FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);

    $coaVersion = CoaVersion::factory()->create(['status' => 'active']);

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

/** GR confirmed 10 unit @ SR_GRIR_UNIT_PRICE, belum diposting jurnalnya. */
function srGrirConfirmedGr(string $number): GoodsReceipt
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
        'quantity_received' => 10,
        'quantity_accepted' => 10,
        'quantity_rejected' => 0,
        'unit_price' => SR_GRIR_UNIT_PRICE,
    ]);

    return $gr->fresh(['items', 'warehouse']);
}

/** Retur confirmed 3 unit atas item GR, belum berjurnal. */
function srGrirConfirmedReturn(GoodsReceipt $gr, string $number): SupplierReturn
{
    $sr = SupplierReturn::factory()->create([
        'status' => 'confirmed',
        'goods_receipt_id' => $gr->id,
        'warehouse_id' => $gr->warehouse_id,
        'return_number' => $number,
        'return_date' => '2026-04-01',
        'journal_entry_id' => null,
        'confirmed_at' => now(),
    ]);

    SupplierReturnItem::factory()->create([
        'supplier_return_id' => $sr->id,
        'goods_receipt_item_id' => $gr->items->first()->id,
        'quantity_returned' => 3,
        'unit_price' => SR_GRIR_UNIT_PRICE,
    ]);

    return $sr->fresh(['items']);
}

/** Bill confirmed atas GR (10 unit), memakai akun beban; sisi debit mengikuti jurnal GR. */
function srGrirBillOfGr(GoodsReceipt $gr, int $expenseAccountId, string $number): SupplierBill
{
    $amount = 10 * SR_GRIR_UNIT_PRICE;

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
        'quantity' => 10,
        'unit_price' => SR_GRIR_UNIT_PRICE,
        'discount_percent' => 0,
        'tax_percent' => 0,
        'line_total' => $amount,
    ]);

    return $bill->fresh(['items']);
}

/** Saldo kredit bersih (kredit - debit) satu akun pada satu jurnal. */
function srGrirNetCredit(JournalEntry $entry, int $accountId): float
{
    $lines = JournalEntryLine::query()
        ->where('journal_entry_id', $entry->id)
        ->where('account_id', $accountId)
        ->get(['debit', 'credit']);

    return round($lines->sum(fn ($l) => (float) $l->credit - (float) $l->debit), 2);
}

/** Saldo kredit bersih satu akun dari seluruh jurnal berstatus posted. */
function srGrirPostedNetCredit(int $accountId): float
{
    $lines = JournalEntryLine::query()
        ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
        ->where('journal_entries.status', 'posted')
        ->where('journal_entry_lines.account_id', $accountId)
        ->get(['journal_entry_lines.debit', 'journal_entry_lines.credit']);

    return round($lines->sum(fn ($l) => (float) $l->credit - (float) $l->debit), 2);
}

test('retur atas GR baru sebelum ditagih mendebit GR/IR 21150, bukan AP 21100', function () {
    $accounts = srGrirAccounts();

    $gr = srGrirConfirmedGr('GR-2026-000201');
    app(PostGoodsReceiptJournalAction::class)->execute($gr);

    $sr = srGrirConfirmedReturn($gr, 'SR-2026-000201');
    $entry = app(PostSupplierReturnJournalAction::class)->execute($sr);
    $returnValue = 3 * SR_GRIR_UNIT_PRICE;

    expect($entry->isBalanced())->toBeTrue()
        ->and(srGrirNetCredit($entry, $accounts['grir']->id))->toBe(-$returnValue)
        ->and(srGrirNetCredit($entry, $accounts['inventory']->id))->toBe($returnValue)
        ->and($entry->lines()->where('account_id', $accounts['ap']->id)->exists())->toBeFalse();
});

test('retur atas GR baru sesudah ditagih mendebit 21150 dan tidak mengubah utang 21100', function () {
    $accounts = srGrirAccounts();

    $gr = srGrirConfirmedGr('GR-2026-000202');
    app(PostGoodsReceiptJournalAction::class)->execute($gr);

    $bill = srGrirBillOfGr($gr, $accounts['expense']->id, 'BILL-2026-000202');
    app(PostSupplierBillJournalAction::class)->execute($bill);

    $apBeforeReturn = srGrirPostedNetCredit($accounts['ap']->id);

    $sr = srGrirConfirmedReturn($gr, 'SR-2026-000202');
    $entry = app(PostSupplierReturnJournalAction::class)->execute($sr);
    $returnValue = 3 * SR_GRIR_UNIT_PRICE;

    expect($entry->isBalanced())->toBeTrue()
        ->and(srGrirNetCredit($entry, $accounts['grir']->id))->toBe(-$returnValue)
        ->and($entry->lines()->where('account_id', $accounts['ap']->id)->exists())->toBeFalse()
        ->and(srGrirPostedNetCredit($accounts['ap']->id))->toBe($apBeforeReturn);
});

test('retur atas GR lama yang mengkredit 21100 mendebit 21100 (akun yang dikredit GR asal)', function () {
    $accounts = srGrirAccounts();

    // Jurnal GR lama: dibuat sebelum perubahan #357, mengkredit 21100 (AP).
    $gr = srGrirConfirmedGr('GR-2026-000203');
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
            ['account_id' => $accounts['inventory']->id, 'debit' => 500000, 'credit' => 0, 'memo' => 'GR'],
            ['account_id' => $accounts['ap']->id, 'debit' => 0, 'credit' => 500000, 'memo' => 'GR'],
        ],
    ]);
    $gr->forceFill(['journal_entry_id' => $legacyEntry->id])->save();

    $sr = srGrirConfirmedReturn($gr->fresh(['items', 'warehouse']), 'SR-2026-000203');
    $entry = app(PostSupplierReturnJournalAction::class)->execute($sr);
    $returnValue = 3 * SR_GRIR_UNIT_PRICE;

    expect($entry->isBalanced())->toBeTrue()
        ->and(srGrirNetCredit($entry, $accounts['ap']->id))->toBe(-$returnValue)
        ->and(srGrirNetCredit($entry, $accounts['grir']->id))->toBe(0.0)
        ->and(srGrirNetCredit($entry, $accounts['inventory']->id))->toBe($returnValue);
});

test('pembatalan retur GR baru membalik baris 21150 asli dan jurnal asli tetap posted', function () {
    $accounts = srGrirAccounts();

    $gr = srGrirConfirmedGr('GR-2026-000204');
    app(PostGoodsReceiptJournalAction::class)->execute($gr);

    $sr = srGrirConfirmedReturn($gr, 'SR-2026-000204');
    $original = app(PostSupplierReturnJournalAction::class)->execute($sr);

    $reversal = app(ReverseSupplierReturnJournalAction::class)->execute($sr->fresh());
    $returnValue = 3 * SR_GRIR_UNIT_PRICE;

    expect($reversal->isBalanced())->toBeTrue()
        ->and(srGrirNetCredit($reversal, $accounts['grir']->id))->toBe($returnValue)
        ->and(srGrirNetCredit($reversal, $accounts['inventory']->id))->toBe(-$returnValue)
        ->and(srGrirNetCredit($reversal, $accounts['ap']->id))->toBe(0.0)
        ->and(JournalEntry::find($original->id)->status)->toBe('posted');
});
