<?php

use App\Actions\AccountingPosting\PostStockTransferJournalAction;
use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('stock-transfer-journal-posting');

function makeStockTransferPostingFiscalYear(): FiscalYear
{
    return FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);
}

/**
 * @return array{inventory: Account, shortage: Account}
 */
function makeStockTransferPostingAccounts(): array
{
    $coaVersion = CoaVersion::factory()->create(['status' => 'active']);

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

    return ['inventory' => $inventory, 'shortage' => $shortage];
}

/**
 * Transfer dengan satu baris: dikirim 10, diterima $received, harga $unitCost.
 */
function makeReceivedStockTransfer(float $received, float $unitCost = 50000.0, string $status = 'received'): StockTransfer
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
        'quantity_received' => $received,
        'unit_cost' => $unitCost,
    ]);

    return $transfer;
}

test('transfer yang baru dikirim (in_transit) tidak membuat jurnal, barang tetap persediaan', function () {
    makeStockTransferPostingFiscalYear();
    makeStockTransferPostingAccounts();

    $transfer = makeReceivedStockTransfer(0, 50000.0, 'in_transit');

    $journal = app(PostStockTransferJournalAction::class)->execute($transfer->fresh());

    expect($journal)->toBeNull()
        ->and($transfer->fresh()->journal_entry_id)->toBeNull()
        ->and(JournalEntry::where('source_type', StockTransfer::class)->count())->toBe(0);
});

test('transfer diterima penuh tidak membuat jurnal selisih', function () {
    makeStockTransferPostingFiscalYear();
    makeStockTransferPostingAccounts();

    $transfer = makeReceivedStockTransfer(10.0);

    $journal = app(PostStockTransferJournalAction::class)->execute($transfer->fresh());

    expect($journal)->toBeNull()
        ->and($transfer->fresh()->journal_entry_id)->toBeNull();
});

test('transfer diterima kurang dijurnal sebagai beban selisih persediaan (Dr 51000 / Cr 11300)', function () {
    makeStockTransferPostingFiscalYear();
    $accounts = makeStockTransferPostingAccounts();

    // Dikirim 10, diterima 8, harga 50.000 -> selisih 2 x 50.000 = 100.000
    $transfer = makeReceivedStockTransfer(8.0, 50000.0);

    $journal = app(PostStockTransferJournalAction::class)->execute($transfer->fresh());

    expect($journal)->not->toBeNull()
        ->and($journal->status)->toBe('posted')
        ->and($journal->source_type)->toBe(StockTransfer::class)
        ->and($journal->source_id)->toBe($transfer->id)
        ->and((float) $journal->total_debit)->toBe(100000.0)
        ->and((float) $journal->total_credit)->toBe(100000.0);

    $shortageLine = $journal->lines()->where('account_id', $accounts['shortage']->id)->first();
    expect((float) $shortageLine->debit)->toBe(100000.0)
        ->and((float) $shortageLine->credit)->toBe(0.0);

    $inventoryLine = $journal->lines()->where('account_id', $accounts['inventory']->id)->first();
    expect((float) $inventoryLine->credit)->toBe(100000.0)
        ->and((float) $inventoryLine->debit)->toBe(0.0);

    expect($transfer->fresh()->journal_entry_id)->toBe($journal->id);
});

test('PostStockTransferJournalAction idempoten: pemanggilan kedua tidak membuat jurnal baru', function () {
    makeStockTransferPostingFiscalYear();
    makeStockTransferPostingAccounts();

    $transfer = makeReceivedStockTransfer(8.0);
    $action = app(PostStockTransferJournalAction::class);

    $first = $action->execute($transfer->fresh());
    $second = $action->execute($transfer->fresh());

    expect($second->id)->toBe($first->id)
        ->and(JournalEntry::where('source_type', StockTransfer::class)->where('source_id', $transfer->id)->count())->toBe(1);
});

test('alur controller: kirim (in_transit) tidak menjurnal, terima kurang menjurnal selisih', function () {
    makeStockTransferPostingFiscalYear();
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

    expect(JournalEntry::where('source_type', StockTransfer::class)->where('source_id', $transfer->id)->count())->toBe(0);

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

    $journal = JournalEntry::where('source_type', StockTransfer::class)->where('source_id', $transfer->id)->first();
    expect($journal)->not->toBeNull()
        ->and((float) $journal->total_debit)->toBe(100000.0);

    expect($journal->lines()->where('account_id', $accounts['shortage']->id)->value('debit'))->toEqual(100000.0);
    expect($journal->lines()->where('account_id', $accounts['inventory']->id)->value('credit'))->toEqual(100000.0);
});

test('transfer dibatalkan sebelum diterima tidak membuat jurnal', function () {
    makeStockTransferPostingFiscalYear();
    makeStockTransferPostingAccounts();

    $transfer = makeReceivedStockTransfer(0, 50000.0, 'cancelled');

    $journal = app(PostStockTransferJournalAction::class)->execute($transfer->fresh());

    expect($journal)->toBeNull()
        ->and(JournalEntry::where('source_type', StockTransfer::class)->count())->toBe(0);
});
