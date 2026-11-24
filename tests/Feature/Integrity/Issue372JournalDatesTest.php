<?php

use App\Actions\AccountingPosting\PostGoodsReceiptJournalAction;
use App\Models\Account;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('stock-posting-issue-372');

/**
 * Issue #372 langkah 3: jurnal mengikuti tanggal movement-nya, dan GL 11300 adalah
 * valuasi pada tanggal mana pun (saldo debit bersih jurnal posted sampai tanggal itu).
 */

/** Saldo debit bersih akun 11300 dari jurnal posted dengan entry_date <= $date. */
function issue372InventoryValuationOn(string $date): float
{
    $account = Account::query()->where('code', '11300')->firstOrFail();

    $lines = JournalEntryLine::query()
        ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
        ->where('journal_entries.status', 'posted')
        ->where('journal_entries.entry_date', '<=', $date)
        ->where('journal_entry_lines.account_id', $account->id)
        ->get(['journal_entry_lines.debit', 'journal_entry_lines.credit']);

    return round($lines->sum(fn ($l) => (float) $l->debit - (float) $l->credit), 2);
}

/**
 * COA aktif untuk tahun 2026 dengan akun 11300 (persediaan) dan 21150 (GR/IR).
 */
function issue372CoaAccounts(): void
{
    createAccountingControlFixture('2026');
}

/**
 * GR confirmed 2026-03-15, 4 unit @ 1000 (nilai 4000).
 */
function issue372ConfirmedReceipt(): GoodsReceipt
{
    /** @var Warehouse $warehouse */
    $warehouse = Warehouse::factory()->create();
    /** @var Product $product */
    $product = Product::factory()->create();
    /** @var User $user */
    $user = User::factory()->create();

    /** @var GoodsReceipt $receipt */
    $receipt = GoodsReceipt::factory()->create([
        'warehouse_id' => $warehouse->id,
        'receipt_date' => '2026-03-15',
        'status' => 'confirmed',
        'confirmed_by' => $user->id,
        'confirmed_at' => now(),
        'journal_entry_id' => null,
    ]);

    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $receipt->id,
        'product_id' => $product->id,
        'quantity_received' => 4,
        'quantity_accepted' => 4,
        'quantity_rejected' => 0,
        'unit_price' => 1000,
    ]);

    return $receipt->refresh();
}

test('GL 11300 mengikuti tanggal jurnal GR: nol sebelum, penuh di antara dan sesudah tanggal GR', function () {
    issue372CoaAccounts();

    $receipt = issue372ConfirmedReceipt();
    app(PostGoodsReceiptJournalAction::class)->execute($receipt);

    /** @var JournalEntry $journal */
    $journal = JournalEntry::query()
        ->where('source_type', GoodsReceipt::class)
        ->where('source_id', $receipt->id)
        ->firstOrFail();

    expect($journal->entry_date->toDateString())->toBe('2026-03-15')
        ->and(issue372InventoryValuationOn('2026-03-14'))->toBe(0.0)
        ->and(issue372InventoryValuationOn('2026-03-15'))->toBe(4000.0)
        ->and(issue372InventoryValuationOn('2026-12-31'))->toBe(4000.0);
});

test('batal GR: stok dibalik bertanggal hari batal, GL 11300 tetap valuasi jurnal GR asli', function () {
    issue372CoaAccounts();

    $receipt = issue372ConfirmedReceipt();
    app(PostGoodsReceiptJournalAction::class)->execute($receipt);
    app(StockPostingService::class)->postGoodsReceipt($receipt->refresh());

    $receipt->update(['status' => 'cancelled']);
    app(StockPostingService::class)->reverseDocument(GoodsReceipt::class, $receipt->id, 'goods_receipt');

    /** @var StockMovement $reversal */
    $reversal = StockMovement::query()
        ->where('movement_type', 'goods_receipt_reversal')
        ->where('reference_id', $receipt->id)
        ->firstOrFail();

    expect($reversal->moved_at->toDateString())->toBe(now()->toDateString())
        ->and(issue372InventoryValuationOn('2026-03-15'))->toBe(4000.0);
});
