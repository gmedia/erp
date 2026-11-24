<?php

use App\Actions\AccountingPosting\PostGoodsReceiptJournalAction;
use App\Actions\AccountingPosting\PostSupplierBillJournalAction;
use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\SupplierBill;
use App\Models\SupplierReturn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('issue-375');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'goods_receipt', 'goods_receipt.edit',
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

const ISSUE375_GR_AMOUNT = 500000.0;

/**
 * @return array{inventory: Account, ap: Account, grir: Account, expense: Account}
 */
function issue375GrAccounts(): array
{
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

    return [
        'inventory' => $make('11300', 'Inventory', 'asset', 'debit'),
        'ap' => $make('21100', 'Accounts Payable', 'liability', 'credit'),
        'grir' => $make('21150', 'Goods Received Not Invoiced', 'liability', 'credit'),
        'expense' => $make('52000', 'General & Admin Expense', 'expense', 'debit'),
    ];
}

function issue375ConfirmedGr(string $number): GoodsReceipt
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
        'unit_price' => ISSUE375_GR_AMOUNT,
    ]);

    return $gr->fresh(['items']);
}

function issue375NetCredit(JournalEntry $entry, int $accountId): float
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

test('pembatalan GR yang sudah diposting membalik baris jurnal asli GR, jurnal asli tetap posted', function () {
    $accounts = issue375GrAccounts();
    $gr = issue375ConfirmedGr('GR-2026-000104');
    $grEntry = app(PostGoodsReceiptJournalAction::class)->execute($gr);

    putJson("/api/goods-receipts/{$gr->id}", ['status' => 'cancelled'])->assertOk();

    $reversal = JournalEntry::query()
        ->where('source_type', GoodsReceipt::class)
        ->where('source_id', $gr->id)
        ->where('id', '!=', $grEntry->id)
        ->first();

    expect($reversal)->not->toBeNull()
        ->and(issue375NetCredit($reversal, $accounts['grir']->id))->toBe(-ISSUE375_GR_AMOUNT)
        ->and(issue375NetCredit($reversal, $accounts['inventory']->id))->toBe(ISSUE375_GR_AMOUNT)
        ->and(JournalEntry::find($grEntry->id)->status)->toBe('posted');

    assertDatabaseHas('goods_receipts', ['id' => $gr->id, 'status' => 'cancelled']);
});

test('batal GR dua kali ditolak 422 dan tidak membuat pembalik kedua', function () {
    issue375GrAccounts();
    $gr = issue375ConfirmedGr('GR-2026-000106');
    app(PostGoodsReceiptJournalAction::class)->execute($gr);

    putJson("/api/goods-receipts/{$gr->id}", ['status' => 'cancelled'])->assertOk();
    putJson("/api/goods-receipts/{$gr->id}", ['status' => 'cancelled'])->assertStatus(422);

    $reversals = JournalEntry::query()
        ->where('source_type', GoodsReceipt::class)
        ->where('source_id', $gr->id)
        ->count();

    expect($reversals)->toBe(2);
});

test('batal GR ditolak 422 bila masih punya retur supplier aktif, GR tetap confirmed', function () {
    issue375GrAccounts();
    $gr = issue375ConfirmedGr('GR-2026-000107');
    app(PostGoodsReceiptJournalAction::class)->execute($gr);

    SupplierReturn::factory()->create([
        'goods_receipt_id' => $gr->id,
        'purchase_order_id' => $gr->purchase_order_id,
        'status' => 'confirmed',
    ]);

    putJson("/api/goods-receipts/{$gr->id}", ['status' => 'cancelled'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('status');

    expect($gr->refresh()->status)->toBe('confirmed');
});

test('batal GR ditolak 422 bila masih punya bill aktif, GR tetap confirmed', function () {
    $accounts = issue375GrAccounts();
    $gr = issue375ConfirmedGr('GR-2026-000108');
    app(PostGoodsReceiptJournalAction::class)->execute($gr);

    $bill = SupplierBill::factory()->confirmed()->create([
        'goods_receipt_id' => $gr->id,
        'bill_number' => 'BILL-2026-000108',
        'bill_date' => '2026-03-20',
        'subtotal' => ISSUE375_GR_AMOUNT,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'grand_total' => ISSUE375_GR_AMOUNT,
        'amount_paid' => 0,
        'amount_due' => ISSUE375_GR_AMOUNT,
    ]);
    $bill->items()->create([
        'account_id' => $accounts['expense']->id,
        'goods_receipt_item_id' => $gr->items->first()->id,
        'description' => 'Pembelian sesuai GR',
        'quantity' => 1,
        'unit_price' => ISSUE375_GR_AMOUNT,
        'discount_percent' => 0,
        'tax_percent' => 0,
        'line_total' => ISSUE375_GR_AMOUNT,
    ]);
    app(PostSupplierBillJournalAction::class)->execute($bill->fresh(['items']));

    putJson("/api/goods-receipts/{$gr->id}", ['status' => 'cancelled'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('status');

    expect($gr->refresh()->status)->toBe('confirmed');
});

test('batal GR ditolak 422 bila periode jurnal sudah tertutup, GR tetap confirmed', function () {
    issue375GrAccounts();
    $gr = issue375ConfirmedGr('GR-2026-000109');
    app(PostGoodsReceiptJournalAction::class)->execute($gr);

    FiscalYear::query()->update(['status' => 'closed']);

    putJson("/api/goods-receipts/{$gr->id}", ['status' => 'cancelled'])->assertStatus(422);

    expect($gr->refresh()->status)->toBe('confirmed');
});
