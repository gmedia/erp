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
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class)->group('gr-ir-price-variance');

/**
 * Keputusan #357 butir 5: GR/IR dikosongkan sebesar nilai penerimaan untuk qty yang
 * ditagih; selisih harga bill vs GR masuk beban selisih persediaan (51000).
 * Tanda selisih mengikuti arah selisih. Tagihan sebagian mengosongkan sebanding qty.
 */
const PV_GR_QTY = 10.0;
const PV_GR_PRICE = 50000.0;

function pvFiscalYear(): void
{
    FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);
}

/** @return array{ap: Account, grir: Account, variance: Account, expense: Account} */
function pvAccounts(): array
{
    $coaVersion = CoaVersion::factory()->create(['status' => 'active']);

    $make = static fn (string $code, string $name, string $type, string $normal): Account => Account::factory()
        ->create([
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
        'variance' => $make('51000', 'Inventory Variance Expense', 'expense', 'debit'),
        'expense' => $make('52000', 'General & Admin Expense', 'expense', 'debit'),
    ];
}

function pvConfirmedGr(
    float $qty = PV_GR_QTY,
    float $price = PV_GR_PRICE,
    string $number = 'GR-2026-000501',
): GoodsReceipt {
    $gr = GoodsReceipt::factory()->create([
        'status' => 'confirmed',
        'gr_number' => $number,
        'receipt_date' => '2026-03-15',
        'journal_entry_id' => null,
        'confirmed_at' => now(),
    ]);

    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr->id,
        'quantity_received' => $qty,
        'quantity_accepted' => $qty,
        'quantity_rejected' => 0,
        'unit_price' => $price,
    ]);

    return $gr->fresh(['items']);
}

/**
 * @param  list<array{qty: float, price: float}>  $lines  Qty dan harga per baris bill, dikaitkan ke item GR pertama.
 */
function pvBill(GoodsReceipt $gr, array $lines, int $expenseAccountId, string $number): SupplierBill
{
    $total = array_sum(array_map(fn (array $l) => $l['qty'] * $l['price'], $lines));

    $bill = SupplierBill::factory()->confirmed()->create([
        'goods_receipt_id' => $gr->id,
        'bill_number' => $number,
        'bill_date' => '2026-03-20',
        'subtotal' => $total,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'grand_total' => $total,
        'amount_paid' => 0,
        'amount_due' => $total,
    ]);

    foreach ($lines as $line) {
        $bill->items()->create([
            'account_id' => $expenseAccountId,
            'goods_receipt_item_id' => $gr->items->first()->id,
            'description' => 'Pembelian sesuai GR',
            'quantity' => $line['qty'],
            'unit_price' => $line['price'],
            'discount_percent' => 0,
            'tax_percent' => 0,
            'line_total' => round($line['qty'] * $line['price'], 2),
        ]);
    }

    return $bill->fresh(['items']);
}

/** Saldo kredit bersih (kredit - debit) satu akun pada satu jurnal. */
function pvNetCredit(JournalEntry $entry, int $accountId): float
{
    $lines = JournalEntryLine::query()
        ->where('journal_entry_id', $entry->id)
        ->where('account_id', $accountId)
        ->get(['debit', 'credit']);

    return round($lines->sum(fn ($l) => (float) $l->credit - (float) $l->debit), 2);
}

/** Saldo GR/IR (21150) dari seluruh jurnal posted sepanjang tes. */
function pvGrIrBalance(int $accountId): float
{
    $lines = JournalEntryLine::query()
        ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
        ->where('journal_entries.status', 'posted')
        ->where('journal_entry_lines.account_id', $accountId)
        ->get(['journal_entry_lines.debit', 'journal_entry_lines.credit']);

    return round($lines->sum(fn ($l) => (float) $l->credit - (float) $l->debit), 2);
}

test('harga bill lebih tinggi dari GR: GR/IR nol, selisih ke 51000 sebagai beban (Dr)', function () {
    pvFiscalYear();
    $a = pvAccounts();
    $gr = pvConfirmedGr();
    app(PostGoodsReceiptJournalAction::class)->execute($gr);

    // Harga bill 55.000 vs GR 50.000, qty 10: selisih +50.000 (beban).
    $bill = pvBill($gr, [['qty' => 10, 'price' => 55000]], $a['expense']->id, 'BILL-2026-000501');
    $entry = app(PostSupplierBillJournalAction::class)->execute($bill);

    expect($entry->isBalanced())->toBeTrue()
        ->and(pvNetCredit($entry, $a['grir']->id))->toBe(-500000.0)
        ->and(pvNetCredit($entry, $a['variance']->id))->toBe(-50000.0)
        ->and(pvNetCredit($entry, $a['ap']->id))->toBe(550000.0)
        ->and(pvGrIrBalance($a['grir']->id))->toBe(0.0);
});

test('harga bill lebih rendah dari GR: GR/IR nol, selisih ke 51000 sebagai pendapatan selisih (Cr)', function () {
    pvFiscalYear();
    $a = pvAccounts();
    $gr = pvConfirmedGr();
    app(PostGoodsReceiptJournalAction::class)->execute($gr);

    // Harga bill 45.000 vs GR 50.000, qty 10: selisih -50.000 (kredit ke 51000).
    $bill = pvBill($gr, [['qty' => 10, 'price' => 45000]], $a['expense']->id, 'BILL-2026-000502');
    $entry = app(PostSupplierBillJournalAction::class)->execute($bill);

    expect($entry->isBalanced())->toBeTrue()
        ->and(pvNetCredit($entry, $a['grir']->id))->toBe(-500000.0)
        ->and(pvNetCredit($entry, $a['variance']->id))->toBe(50000.0)
        ->and(pvNetCredit($entry, $a['ap']->id))->toBe(450000.0)
        ->and(pvGrIrBalance($a['grir']->id))->toBe(0.0);
});

test('tagihan sebagian: GR/IR dikosongkan sebanding qty yang ditagih, sisa tetap di GR/IR', function () {
    pvFiscalYear();
    $a = pvAccounts();
    $gr = pvConfirmedGr();
    app(PostGoodsReceiptJournalAction::class)->execute($gr);

    // Tagih 4 dari 10 qty, harga sama: GR/IR dikosongkan 200.000, sisa 300.000.
    $bill = pvBill($gr, [['qty' => 4, 'price' => PV_GR_PRICE]], $a['expense']->id, 'BILL-2026-000503');
    $entry = app(PostSupplierBillJournalAction::class)->execute($bill);

    expect($entry->isBalanced())->toBeTrue()
        ->and(pvNetCredit($entry, $a['grir']->id))->toBe(-200000.0)
        ->and(pvNetCredit($entry, $a['variance']->id))->toBe(0.0)
        ->and(pvGrIrBalance($a['grir']->id))->toBe(300000.0);
});

test('dua bill untuk satu GR: qty ditagih kumulatif, GR/IR nol setelah seluruh qty ditagih', function () {
    pvFiscalYear();
    $a = pvAccounts();
    $gr = pvConfirmedGr();
    app(PostGoodsReceiptJournalAction::class)->execute($gr);

    $billA = pvBill($gr, [['qty' => 6, 'price' => PV_GR_PRICE]], $a['expense']->id, 'BILL-2026-000504');
    app(PostSupplierBillJournalAction::class)->execute($billA);

    $billB = pvBill($gr, [['qty' => 4, 'price' => PV_GR_PRICE]], $a['expense']->id, 'BILL-2026-000505');
    $entryB = app(PostSupplierBillJournalAction::class)->execute($billB);

    expect($entryB->isBalanced())->toBeTrue()
        ->and(pvNetCredit($entryB, $a['grir']->id))->toBe(-200000.0)
        ->and(pvGrIrBalance($a['grir']->id))->toBe(0.0);
});

test('bill memuat item dari GR berbeda: tiap item mengosongkan GR/IR GR asalnya', function () {
    pvFiscalYear();
    $a = pvAccounts();

    $grA = pvConfirmedGr(10, 50000, 'GR-2026-000506');
    $grB = pvConfirmedGr(4, 25000, 'GR-2026-000507');
    app(PostGoodsReceiptJournalAction::class)->execute($grA);
    app(PostGoodsReceiptJournalAction::class)->execute($grB);

    // Satu bill, dua baris: masing-masing dikaitkan ke item GR yang berbeda.
    $bill = SupplierBill::factory()->confirmed()->create([
        'goods_receipt_id' => null,
        'bill_number' => 'BILL-2026-000506',
        'bill_date' => '2026-03-20',
        'subtotal' => 600000,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'grand_total' => 600000,
        'amount_paid' => 0,
        'amount_due' => 600000,
    ]);
    $bill->items()->create([
        'account_id' => $a['expense']->id,
        'goods_receipt_item_id' => $grA->items->first()->id,
        'description' => 'Dari GR A',
        'quantity' => 10,
        'unit_price' => 50000,
        'discount_percent' => 0,
        'tax_percent' => 0,
        'line_total' => 500000,
    ]);
    $bill->items()->create([
        'account_id' => $a['expense']->id,
        'goods_receipt_item_id' => $grB->items->first()->id,
        'description' => 'Dari GR B',
        'quantity' => 4,
        'unit_price' => 25000,
        'discount_percent' => 0,
        'tax_percent' => 0,
        'line_total' => 100000,
    ]);

    $entry = app(PostSupplierBillJournalAction::class)->execute($bill->fresh(['items']));

    expect($entry->isBalanced())->toBeTrue()
        ->and(pvGrIrBalance($a['grir']->id))->toBe(0.0)
        ->and(pvNetCredit($entry, $a['grir']->id))->toBe(-600000.0);
});

test('qty bill melebihi qty GR: kelebihan qty tidak boleh mengosongkan GR/IR di atas nilai GR', function () {
    pvFiscalYear();
    $a = pvAccounts();
    $gr = pvConfirmedGr();
    app(PostGoodsReceiptJournalAction::class)->execute($gr);

    // Tagih 12 dari 10 qty yang diterima. Hanya 10 qty yang boleh mengosongkan GR/IR.
    $bill = pvBill($gr, [['qty' => 12, 'price' => PV_GR_PRICE]], $a['expense']->id, 'BILL-2026-000507');

    expect(fn () => app(PostSupplierBillJournalAction::class)->execute($bill))
        ->toThrow(ValidationException::class);

    expect(pvGrIrBalance($a['grir']->id))->toBe(500000.0);
});

test('GR lama (kredit 21100) dengan bill berselisih harga: selisih ke 51000, utang tidak ganda', function () {
    pvFiscalYear();
    $a = pvAccounts();
    $gr = pvConfirmedGr(10, PV_GR_PRICE, 'GR-2026-000508');

    // Jurnal GR lama: mengkredit 21100, bukan GR/IR.
    $legacy = app(CreateJournalEntryAction::class)->execute([
        'entry_date' => '2026-03-15',
        'reference' => $gr->gr_number,
        'description' => "Goods Receipt {$gr->gr_number}",
        'status' => 'posted',
        'journal_type' => 'system',
        'source_type' => GoodsReceipt::class,
        'source_id' => $gr->id,
        'branch_id' => null,
        'lines' => [
            ['account_id' => $a['inventory']->id, 'debit' => 500000, 'credit' => 0, 'memo' => 'GR'],
            ['account_id' => $a['ap']->id, 'debit' => 0, 'credit' => 500000, 'memo' => 'GR'],
        ],
    ]);
    $gr->forceFill(['journal_entry_id' => $legacy->id])->save();

    $bill = pvBill($gr, [['qty' => 10, 'price' => 55000]], $a['expense']->id, 'BILL-2026-000508');
    $entry = app(PostSupplierBillJournalAction::class)->execute($bill);

    // Bill 550.000 mendebit 21100 (akun GR lama) 500.000 dan mengkredit AP 550.000:
    // 21100 bersih = kredit 50.000 (selisih harga yang belum dibayar), 51000 debit 50.000.
    expect($entry->isBalanced())->toBeTrue()
        ->and(pvNetCredit($entry, $a['variance']->id))->toBe(-50000.0)
        ->and(pvNetCredit($entry, $a['ap']->id))->toBe(50000.0);
});
