<?php

use App\Actions\AccountingPosting\PostStockAdjustmentJournalAction;
use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('stock-adjustment-journal-posting');

function makeStockAdjPostingFiscalYear(): FiscalYear
{
    return FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);
}

function makeStockAdjPostingAccounts(): array
{
    $coaVersion = CoaVersion::factory()->create(['status' => 'active']);

    $inventoryAccount = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '11300',
        'name' => 'Inventory',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);

    $expenseAccount = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '51000',
        'name' => 'Cost of Goods Sold',
        'type' => 'expense',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);

    return [
        'coa_version' => $coaVersion,
        'inventory' => $inventoryAccount,
        'expense' => $expenseAccount,
    ];
}

test('PostStockAdjustmentJournalAction creates a balanced posted journal entry for negative adjustment', function () {
    makeStockAdjPostingFiscalYear();
    $accounts = makeStockAdjPostingAccounts();
    $approver = User::factory()->create();

    $adjustment = StockAdjustment::factory()->create([
        'status' => 'approved',
        'adjustment_number' => 'SA-2026-000001',
        'adjustment_date' => '2026-03-15',
        'adjustment_type' => 'damage',
        'journal_entry_id' => null,
        'approved_by' => $approver->id,
        'approved_at' => now(),
    ]);

    StockAdjustmentItem::factory()->create([
        'stock_adjustment_id' => $adjustment->id,
        'quantity_adjusted' => -10,
        'unit_cost' => 50000,
        'total_cost' => 500000,
    ]);

    $action = app(PostStockAdjustmentJournalAction::class);
    $journalEntry = $action->execute($adjustment->fresh());

    expect($journalEntry)->not->toBeNull()
        ->and($journalEntry->status)->toBe('posted')
        ->and($journalEntry->journal_type)->toBe('system')
        ->and($journalEntry->source_type)->toBe(StockAdjustment::class)
        ->and($journalEntry->source_id)->toBe($adjustment->id)
        ->and((float) $journalEntry->total_debit)->toBe(500000.0)
        ->and((float) $journalEntry->total_credit)->toBe(500000.0);

    assertDatabaseHas('stock_adjustments', [
        'id' => $adjustment->id,
        'journal_entry_id' => $journalEntry->id,
    ]);

    $expenseLine = $journalEntry->lines()->where('account_id', $accounts['expense']->id)->first();
    expect((float) $expenseLine->debit)->toBe(500000.0)
        ->and((float) $expenseLine->credit)->toBe(0.0);

    $inventoryLine = $journalEntry->lines()->where('account_id', $accounts['inventory']->id)->first();
    expect((float) $inventoryLine->credit)->toBe(500000.0)
        ->and((float) $inventoryLine->debit)->toBe(0.0);
});

test('PostStockAdjustmentJournalAction creates a balanced posted journal entry for positive adjustment', function () {
    makeStockAdjPostingFiscalYear();
    $accounts = makeStockAdjPostingAccounts();
    $approver = User::factory()->create();

    $adjustment = StockAdjustment::factory()->create([
        'status' => 'approved',
        'adjustment_number' => 'SA-2026-000002',
        'adjustment_date' => '2026-04-01',
        'adjustment_type' => 'correction',
        'journal_entry_id' => null,
        'approved_by' => $approver->id,
        'approved_at' => now(),
    ]);

    StockAdjustmentItem::factory()->create([
        'stock_adjustment_id' => $adjustment->id,
        'quantity_adjusted' => 5,
        'unit_cost' => 100000,
        'total_cost' => 500000,
    ]);

    $action = app(PostStockAdjustmentJournalAction::class);
    $journalEntry = $action->execute($adjustment->fresh());

    expect($journalEntry)->not->toBeNull()
        ->and((float) $journalEntry->total_debit)->toBe(500000.0)
        ->and((float) $journalEntry->total_credit)->toBe(500000.0);

    $inventoryLine = $journalEntry->lines()->where('account_id', $accounts['inventory']->id)->first();
    expect((float) $inventoryLine->debit)->toBe(500000.0)
        ->and((float) $inventoryLine->credit)->toBe(0.0);

    $expenseLine = $journalEntry->lines()->where('account_id', $accounts['expense']->id)->first();
    expect((float) $expenseLine->credit)->toBe(500000.0)
        ->and((float) $expenseLine->debit)->toBe(0.0);
});

test('PostStockAdjustmentJournalAction is idempotent', function () {
    makeStockAdjPostingFiscalYear();
    makeStockAdjPostingAccounts();
    $approver = User::factory()->create();

    $adjustment = StockAdjustment::factory()->create([
        'status' => 'approved',
        'adjustment_number' => 'SA-2026-000003',
        'adjustment_date' => '2026-03-15',
        'journal_entry_id' => null,
        'approved_by' => $approver->id,
        'approved_at' => now(),
    ]);

    StockAdjustmentItem::factory()->create([
        'stock_adjustment_id' => $adjustment->id,
        'quantity_adjusted' => -5,
        'unit_cost' => 200000,
        'total_cost' => 1000000,
    ]);

    $action = app(PostStockAdjustmentJournalAction::class);

    $first = $action->execute($adjustment->fresh());
    $second = $action->execute($adjustment->fresh());

    expect($second->id)->toBe($first->id);
    expect(JournalEntry::count())->toBe(1);
});

test('PostStockAdjustmentJournalAction returns null when not approved', function () {
    makeStockAdjPostingFiscalYear();
    makeStockAdjPostingAccounts();

    $adjustment = StockAdjustment::factory()->create([
        'status' => 'draft',
        'journal_entry_id' => null,
    ]);

    $action = app(PostStockAdjustmentJournalAction::class);

    expect($action->execute($adjustment))->toBeNull();
    expect(JournalEntry::count())->toBe(0);
});

test('PostStockAdjustmentJournalAction throws when no items exist', function () {
    makeStockAdjPostingFiscalYear();
    makeStockAdjPostingAccounts();
    $approver = User::factory()->create();

    $adjustment = StockAdjustment::factory()->create([
        'status' => 'approved',
        'adjustment_date' => '2026-03-15',
        'journal_entry_id' => null,
        'approved_by' => $approver->id,
        'approved_at' => now(),
    ]);

    $action = app(PostStockAdjustmentJournalAction::class);

    expect(fn () => $action->execute($adjustment->fresh()))
        ->toThrow(ValidationException::class);
});

test('StockAdjustmentController update triggers journal posting on approve transition', function () {
    $user = createTestUserWithPermissions([
        'stock_adjustment',
        'stock_adjustment.create',
        'stock_adjustment.edit',
        'stock_adjustment.delete',
    ]);
    Sanctum::actingAs($user, ['*']);

    makeStockAdjPostingFiscalYear();
    makeStockAdjPostingAccounts();

    $adjustment = StockAdjustment::factory()->create([
        'status' => 'pending_approval',
        'adjustment_number' => 'SA-2026-000004',
        'adjustment_date' => '2026-03-15',
        'adjustment_type' => 'shrinkage',
        'journal_entry_id' => null,
        'inventory_stocktake_id' => null,
        'approved_by' => null,
        'approved_at' => null,
    ]);

    StockAdjustmentItem::factory()->create([
        'stock_adjustment_id' => $adjustment->id,
        'quantity_adjusted' => -20,
        'unit_cost' => 25000,
        'total_cost' => 500000,
    ]);

    putJson("/api/stock-adjustments/{$adjustment->id}", [
        'status' => 'approved',
    ])->assertOk()->assertJsonPath('data.status', 'approved');

    $adjustment->refresh();
    expect($adjustment->journal_entry_id)->not->toBeNull();

    assertDatabaseHas('journal_entries', [
        'id' => $adjustment->journal_entry_id,
        'status' => 'posted',
        'journal_type' => 'system',
        'source_type' => StockAdjustment::class,
        'source_id' => $adjustment->id,
    ]);
});

test('StockAdjustmentController update does not double-post on subsequent saves', function () {
    $user = createTestUserWithPermissions([
        'stock_adjustment',
        'stock_adjustment.create',
        'stock_adjustment.edit',
        'stock_adjustment.delete',
    ]);
    Sanctum::actingAs($user, ['*']);

    makeStockAdjPostingFiscalYear();
    makeStockAdjPostingAccounts();

    $adjustment = StockAdjustment::factory()->create([
        'status' => 'pending_approval',
        'adjustment_number' => 'SA-2026-000005',
        'adjustment_date' => '2026-03-15',
        'journal_entry_id' => null,
        'inventory_stocktake_id' => null,
        'approved_by' => null,
        'approved_at' => null,
    ]);

    StockAdjustmentItem::factory()->create([
        'stock_adjustment_id' => $adjustment->id,
        'quantity_adjusted' => -10,
        'unit_cost' => 100000,
        'total_cost' => 1000000,
    ]);

    putJson("/api/stock-adjustments/{$adjustment->id}", ['status' => 'approved'])->assertOk();
    putJson("/api/stock-adjustments/{$adjustment->id}", ['notes' => 'updated after approval'])->assertOk();

    expect(JournalEntry::where('source_type', StockAdjustment::class)->count())->toBe(1);
});
