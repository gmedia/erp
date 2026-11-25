<?php

use App\Actions\AccountingPosting\PostStockTransferJournalAction;
use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\StockPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('stock-transfer-journal-posting');

/**
 * Akun yang dipakai jurnal transfer (issue #495).
 *
 * @return array{inventory: Account, shortage: Account, transit: Account}
 */
function makeStockTransferPostingAccounts(): array
{
    $fiscalYear = FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);
    $coaVersion = CoaVersion::factory()->create([
        'status' => 'active',
        'fiscal_year_id' => $fiscalYear->id,
    ]);

    $inventory = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '11300',
        'name' => 'Inventory',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);

    $shortage = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '51000',
        'name' => 'Inventory Shortage Expense',
        'type' => 'expense',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);

    $transit = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '11310',
        'name' => 'Persediaan dalam Perjalanan',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);

    Setting::set('inventory_in_transit_account_id', (string) $transit->id);

    return ['inventory' => $inventory, 'shortage' => $shortage, 'transit' => $transit];
}

/**
 * Transfer satu baris: dikirim 10 @ $unitCost, diterima $received.
 */
function makeStockTransferForJournal(float $received, string $status, float $unitCost = 50000.0): StockTransfer
{
    $from = Warehouse::factory()->create();
    $to = Warehouse::factory()->create();

    $transfer = StockTransfer::factory()->create([
        'from_warehouse_id' => $from->id,
        'to_warehouse_id' => $to->id,
        'status' => $status,
        'transfer_number' => 'ST-2026-000001',
        'transfer_date' => '2026-03-10',
        'received_at' => $status === 'received' ? '2026-03-12 09:00:00' : null,
        'journal_entry_id' => null,
    ]);

    StockTransferItem::factory()->create([
        'stock_transfer_id' => $transfer->id,
        'quantity' => 10,
        'quantity_received' => $status === 'received' ? $received : 0,
        'unit_cost' => $unitCost,
    ]);

    return $transfer;
}

/**
 * Stok awal 100 @ 50.000 di gudang asal, lalu movement transfer diposting lewat
 * StockPostingService (transfer_out/transfer_in), seperti alur produksi. Jurnal
 * membaca biaya dari movement tersebut, jadi tes tidak memakai data buatan.
 */
function seedOpeningStockForTransfer(StockTransfer $transfer, float $unitCost = 50000.0): void
{
    StockMovement::factory()->create([
        'warehouse_id' => $transfer->from_warehouse_id,
        'product_id' => $transfer->items()->value('product_id'),
        'movement_type' => 'goods_receipt',
        'quantity_in' => 100,
        'quantity_out' => 0,
        'balance_after' => 100,
        'unit_cost' => $unitCost,
        'moved_at' => '2026-01-01 00:00:00',
    ]);

    $stockPosting = app(StockPostingService::class);
    $stockPosting->postStockTransferDispatch($transfer->fresh());

    if ($transfer->fresh()->status === 'received') {
        $stockPosting->postStockTransferReceive($transfer->fresh());
    }
}

/**
 * Jurnal transfer pada satu tahap (journal_type), untuk membandingkan tahap per tahap.
 */
function stockTransferJournalOfType(StockTransfer $transfer, string $journalType): ?JournalEntry
{
    return stockTransferJournals($transfer)->firstWhere('journal_type', $journalType);
}

/**
 * @return Collection<int, JournalEntry>
 */
function stockTransferJournals(StockTransfer $transfer)
{
    return JournalEntry::query()
        ->where('source_type', StockTransfer::class)
        ->where('source_id', $transfer->id)
        ->orderBy('id')
        ->get();
}

test('dispatch membuat jurnal Dr persediaan dalam perjalanan / Cr persediaan asal sebesar qty kirim x avg asal', function () {
    $accounts = makeStockTransferPostingAccounts();
    $transfer = makeStockTransferForJournal(0, 'in_transit');
    seedOpeningStockForTransfer($transfer);

    $journal = app(PostStockTransferJournalAction::class)->executeDispatch($transfer->fresh());

    expect($journal)->not->toBeNull()
        ->and($journal->journal_type)->toBe(PostStockTransferJournalAction::TYPE_DISPATCH)
        ->and((float) $journal->total_debit)->toBe(500000.0)
        ->and((float) $journal->total_credit)->toBe(500000.0)
        ->and($journal->lines()->where('account_id', $accounts['transit']->id)->value('debit'))->toEqual(500000.0)
        ->and($journal->lines()->where('account_id', $accounts['inventory']->id)->value('credit'))->toEqual(500000.0);
});

test('receive penuh membuat jurnal Dr persediaan tujuan / Cr persediaan dalam perjalanan', function () {
    $accounts = makeStockTransferPostingAccounts();
    $transfer = makeStockTransferForJournal(10.0, 'received');
    seedOpeningStockForTransfer($transfer);
    $action = app(PostStockTransferJournalAction::class);
    $action->executeDispatch($transfer->fresh());

    $journal = $action->executeReceive($transfer->fresh());

    expect($journal)->not->toBeNull()
        ->and($journal->journal_type)->toBe(PostStockTransferJournalAction::TYPE_RECEIVE)
        ->and((float) $journal->total_debit)->toBe(500000.0)
        ->and($journal->lines()->where('account_id', $accounts['inventory']->id)->value('debit'))->toEqual(500000.0)
        ->and($journal->lines()->where('account_id', $accounts['transit']->id)->value('credit'))->toEqual(500000.0);

    expect(stockTransferJournals($transfer))->toHaveCount(2);
});

test('receive sebagian dengan selisih membuat Dr beban selisih / Cr persediaan dalam perjalanan', function () {
    $accounts = makeStockTransferPostingAccounts();
    // Dikirim 10, diterima 8 @ 50.000: diterima 400.000, selisih 100.000.
    $transfer = makeStockTransferForJournal(8.0, 'received');
    seedOpeningStockForTransfer($transfer);
    $action = app(PostStockTransferJournalAction::class);
    $action->executeDispatch($transfer->fresh());

    $receive = $action->executeReceive($transfer->fresh());
    $shortage = $action->executeShortage($transfer->fresh());

    expect((float) $receive->total_debit)->toBe(400000.0)
        ->and($receive->lines()->where('account_id', $accounts['transit']->id)->value('credit'))->toEqual(400000.0);

    expect($shortage)->not->toBeNull()
        ->and($shortage->journal_type)->toBe(PostStockTransferJournalAction::TYPE_SHORTAGE)
        ->and((float) $shortage->total_debit)->toBe(100000.0)
        ->and($shortage->lines()->where('account_id', $accounts['shortage']->id)->value('debit'))->toEqual(100000.0)
        ->and($shortage->lines()->where('account_id', $accounts['transit']->id)->value('credit'))->toEqual(100000.0);
});

test('batal sebelum diterima membuat jurnal pembalik dari jurnal dispatch', function () {
    $accounts = makeStockTransferPostingAccounts();
    // Alur nyata: transfer sudah dikirim (in_transit), lalu dibatalkan sebelum diterima.
    $transfer = makeStockTransferForJournal(0, 'in_transit');
    seedOpeningStockForTransfer($transfer);
    $action = app(PostStockTransferJournalAction::class);
    $dispatch = $action->executeDispatch($transfer->fresh());

    $transfer->forceFill(['status' => 'cancelled'])->save();
    $reversal = $action->executeDispatchReversal($transfer->fresh());

    expect($reversal)->not->toBeNull()
        ->and($reversal->journal_type)->toBe(PostStockTransferJournalAction::TYPE_DISPATCH_REVERSAL)
        ->and((float) $reversal->total_debit)->toBe((float) $dispatch->total_credit)
        ->and($reversal->lines()->where('account_id', $accounts['inventory']->id)->value('debit'))->toEqual(500000.0)
        ->and($reversal->lines()->where('account_id', $accounts['transit']->id)->value('credit'))->toEqual(500000.0);

    expect($action->executeDispatchReversal($transfer->fresh())->id)->toBe($reversal->id);
    expect(stockTransferJournals($transfer)->where('journal_type', PostStockTransferJournalAction::TYPE_DISPATCH_REVERSAL))
        ->toHaveCount(1);
});

test('PostStockTransferJournalAction idempoten: pemanggilan ulang tahap yang sama tidak membuat jurnal baru', function () {
    makeStockTransferPostingAccounts();
    $transfer = makeStockTransferForJournal(10.0, 'received');
    seedOpeningStockForTransfer($transfer);
    $action = app(PostStockTransferJournalAction::class);

    $first = $action->executeDispatch($transfer->fresh());
    $second = $action->executeDispatch($transfer->fresh());

    expect($second->id)->toBe($first->id)
        ->and(stockTransferJournals($transfer)->where('journal_type', PostStockTransferJournalAction::TYPE_DISPATCH))->toHaveCount(1);
});

test('dispatch tanpa akun persediaan dalam perjalanan ditolak 422', function () {
    makeStockTransferPostingAccounts();
    Setting::set('inventory_in_transit_account_id', null);
    $transfer = makeStockTransferForJournal(0, 'in_transit');
    seedOpeningStockForTransfer($transfer);

    app(PostStockTransferJournalAction::class)->executeDispatch($transfer->fresh());
})->throws(ValidationException::class);

test('alur controller: kirim, terima kurang, dan membatalkan menjurnal sesuai tahap', function () {
    $accounts = makeStockTransferPostingAccounts();

    $user = createTestUserWithPermissions(['stock_transfer', 'stock_transfer.edit']);
    Sanctum::actingAs($user, ['*']);

    $from = Warehouse::factory()->create();
    $to = Warehouse::factory()->create();
    $product = Product::factory()->create();
    $unit = Unit::factory()->create();

    StockMovement::factory()->create([
        'warehouse_id' => $from->id,
        'product_id' => $product->id,
        'movement_type' => 'goods_receipt',
        'quantity_in' => 100,
        'quantity_out' => 0,
        'balance_after' => 100,
        'unit_cost' => 50000,
        'moved_at' => '2026-01-01 00:00:00',
    ]);

    $transfer = StockTransfer::factory()->create([
        'from_warehouse_id' => $from->id,
        'to_warehouse_id' => $to->id,
        'status' => 'draft',
        'transfer_number' => 'ST-2026-000002',
        'transfer_date' => '2026-03-10',
    ]);

    $items = [[
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'quantity' => 10,
        'unit_cost' => 50000,
    ]];

    putJson("/api/stock-transfers/{$transfer->id}", ['status' => 'in_transit', 'items' => $items])->assertOk();

    expect(stockTransferJournals($transfer)->where('journal_type', PostStockTransferJournalAction::TYPE_DISPATCH))
        ->toHaveCount(1);

    putJson("/api/stock-transfers/{$transfer->id}", [
        'status' => 'received',
        'items' => [[
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'quantity' => 10,
            'quantity_received' => 8,
            'unit_cost' => 50000,
        ]],
    ])->assertOk();

    $shortage = stockTransferJournalOfType($transfer, PostStockTransferJournalAction::TYPE_SHORTAGE);
    expect($shortage)->not->toBeNull()
        ->and((float) $shortage->total_debit)->toBe(100000.0)
        ->and($shortage->lines()->where('account_id', $accounts['shortage']->id)->value('debit'))->toEqual(100000.0);
});
