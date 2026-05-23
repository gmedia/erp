<?php

use App\Actions\AccountingPosting\PostGoodsReceiptJournalAction;
use App\Actions\AccountingPosting\PostSupplierReturnJournalAction;
use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\JournalEntry;
use App\Models\SupplierReturn;
use App\Models\SupplierReturnItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('gr-sr-journal-posting');

function makeGrSrPostingFiscalYear(): FiscalYear
{
    return FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);
}

function makeGrSrPostingAccounts(): array
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

    $apAccount = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '21100',
        'name' => 'Accounts Payable',
        'type' => 'liability',
        'normal_balance' => 'credit',
        'is_active' => true,
    ]);

    return [
        'inventory' => $inventoryAccount,
        'ap' => $apAccount,
    ];
}

test('PostGoodsReceiptJournalAction creates a balanced posted journal entry', function () {
    makeGrSrPostingFiscalYear();
    $accounts = makeGrSrPostingAccounts();
    $confirmer = User::factory()->create();

    $gr = GoodsReceipt::factory()->create([
        'status' => 'confirmed',
        'gr_number' => 'GR-2026-000001',
        'receipt_date' => '2026-03-15',
        'journal_entry_id' => null,
        'confirmed_by' => $confirmer->id,
        'confirmed_at' => now(),
    ]);

    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr->id,
        'quantity_received' => 10,
        'quantity_accepted' => 10,
        'quantity_rejected' => 0,
        'unit_price' => 50000,
    ]);

    $action = app(PostGoodsReceiptJournalAction::class);
    $journalEntry = $action->execute($gr->fresh());

    expect($journalEntry)->not->toBeNull()
        ->and($journalEntry->status)->toBe('posted')
        ->and($journalEntry->journal_type)->toBe('system')
        ->and($journalEntry->source_type)->toBe(GoodsReceipt::class)
        ->and($journalEntry->source_id)->toBe($gr->id)
        ->and((float) $journalEntry->total_debit)->toBe(500000.0)
        ->and((float) $journalEntry->total_credit)->toBe(500000.0);

    assertDatabaseHas('goods_receipts', [
        'id' => $gr->id,
        'journal_entry_id' => $journalEntry->id,
    ]);

    $inventoryLine = $journalEntry->lines()->where('account_id', $accounts['inventory']->id)->first();
    expect((float) $inventoryLine->debit)->toBe(500000.0);

    $apLine = $journalEntry->lines()->where('account_id', $accounts['ap']->id)->first();
    expect((float) $apLine->credit)->toBe(500000.0);
});

test('PostGoodsReceiptJournalAction is idempotent', function () {
    makeGrSrPostingFiscalYear();
    makeGrSrPostingAccounts();
    $confirmer = User::factory()->create();

    $gr = GoodsReceipt::factory()->create([
        'status' => 'confirmed',
        'gr_number' => 'GR-2026-000002',
        'receipt_date' => '2026-03-15',
        'journal_entry_id' => null,
        'confirmed_by' => $confirmer->id,
        'confirmed_at' => now(),
    ]);

    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr->id,
        'quantity_accepted' => 5,
        'unit_price' => 100000,
    ]);

    $action = app(PostGoodsReceiptJournalAction::class);

    $first = $action->execute($gr->fresh());
    $second = $action->execute($gr->fresh());

    expect($second->id)->toBe($first->id);
    expect(JournalEntry::count())->toBe(1);
});

test('PostGoodsReceiptJournalAction returns null when not confirmed', function () {
    makeGrSrPostingFiscalYear();
    makeGrSrPostingAccounts();

    $gr = GoodsReceipt::factory()->create([
        'status' => 'draft',
        'journal_entry_id' => null,
    ]);

    expect(app(PostGoodsReceiptJournalAction::class)->execute($gr))->toBeNull();
    expect(JournalEntry::count())->toBe(0);
});

test('PostSupplierReturnJournalAction creates a balanced posted journal entry', function () {
    makeGrSrPostingFiscalYear();
    $accounts = makeGrSrPostingAccounts();
    $confirmer = User::factory()->create();

    $sr = SupplierReturn::factory()->create([
        'status' => 'confirmed',
        'return_number' => 'SR-2026-000001',
        'return_date' => '2026-04-01',
        'journal_entry_id' => null,
        'confirmed_by' => $confirmer->id,
        'confirmed_at' => now(),
    ]);

    SupplierReturnItem::factory()->create([
        'supplier_return_id' => $sr->id,
        'quantity_returned' => 3,
        'unit_price' => 50000,
    ]);

    $action = app(PostSupplierReturnJournalAction::class);
    $journalEntry = $action->execute($sr->fresh());

    expect($journalEntry)->not->toBeNull()
        ->and($journalEntry->status)->toBe('posted')
        ->and($journalEntry->journal_type)->toBe('system')
        ->and($journalEntry->source_type)->toBe(SupplierReturn::class)
        ->and($journalEntry->source_id)->toBe($sr->id)
        ->and((float) $journalEntry->total_debit)->toBe(150000.0)
        ->and((float) $journalEntry->total_credit)->toBe(150000.0);

    assertDatabaseHas('supplier_returns', [
        'id' => $sr->id,
        'journal_entry_id' => $journalEntry->id,
    ]);

    $apLine = $journalEntry->lines()->where('account_id', $accounts['ap']->id)->first();
    expect((float) $apLine->debit)->toBe(150000.0);

    $inventoryLine = $journalEntry->lines()->where('account_id', $accounts['inventory']->id)->first();
    expect((float) $inventoryLine->credit)->toBe(150000.0);
});

test('PostSupplierReturnJournalAction is idempotent', function () {
    makeGrSrPostingFiscalYear();
    makeGrSrPostingAccounts();
    $confirmer = User::factory()->create();

    $sr = SupplierReturn::factory()->create([
        'status' => 'confirmed',
        'return_number' => 'SR-2026-000002',
        'return_date' => '2026-04-01',
        'journal_entry_id' => null,
        'confirmed_by' => $confirmer->id,
        'confirmed_at' => now(),
    ]);

    SupplierReturnItem::factory()->create([
        'supplier_return_id' => $sr->id,
        'quantity_returned' => 2,
        'unit_price' => 75000,
    ]);

    $action = app(PostSupplierReturnJournalAction::class);

    $first = $action->execute($sr->fresh());
    $second = $action->execute($sr->fresh());

    expect($second->id)->toBe($first->id);
    expect(JournalEntry::count())->toBe(1);
});

test('PostSupplierReturnJournalAction returns null when not confirmed', function () {
    makeGrSrPostingFiscalYear();
    makeGrSrPostingAccounts();

    $sr = SupplierReturn::factory()->create([
        'status' => 'draft',
        'journal_entry_id' => null,
        'confirmed_by' => null,
        'confirmed_at' => null,
    ]);

    expect(app(PostSupplierReturnJournalAction::class)->execute($sr))->toBeNull();
    expect(JournalEntry::count())->toBe(0);
});

test('GoodsReceiptController update triggers journal posting on confirm transition', function () {
    $user = createTestUserWithPermissions([
        'goods_receipt',
        'goods_receipt.create',
        'goods_receipt.edit',
        'goods_receipt.delete',
    ]);
    Sanctum::actingAs($user, ['*']);

    makeGrSrPostingFiscalYear();
    makeGrSrPostingAccounts();

    $gr = GoodsReceipt::factory()->create([
        'status' => 'draft',
        'gr_number' => 'GR-2026-000003',
        'receipt_date' => '2026-03-15',
        'journal_entry_id' => null,
        'confirmed_by' => null,
        'confirmed_at' => null,
    ]);

    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr->id,
        'quantity_received' => 10,
        'quantity_accepted' => 10,
        'quantity_rejected' => 0,
        'unit_price' => 80000,
    ]);

    putJson("/api/goods-receipts/{$gr->id}", [
        'status' => 'confirmed',
    ])->assertOk()->assertJsonPath('data.status', 'confirmed');

    $gr->refresh();
    expect($gr->journal_entry_id)->not->toBeNull();

    assertDatabaseHas('journal_entries', [
        'id' => $gr->journal_entry_id,
        'status' => 'posted',
        'journal_type' => 'system',
        'source_type' => GoodsReceipt::class,
        'source_id' => $gr->id,
    ]);
});

test('SupplierReturnController update triggers journal posting on confirm transition', function () {
    $user = createTestUserWithPermissions([
        'supplier_return',
        'supplier_return.create',
        'supplier_return.edit',
        'supplier_return.delete',
    ]);
    Sanctum::actingAs($user, ['*']);

    makeGrSrPostingFiscalYear();
    makeGrSrPostingAccounts();

    $sr = SupplierReturn::factory()->create([
        'status' => 'draft',
        'return_number' => 'SR-2026-000003',
        'return_date' => '2026-04-01',
        'journal_entry_id' => null,
        'confirmed_by' => null,
        'confirmed_at' => null,
    ]);

    SupplierReturnItem::factory()->create([
        'supplier_return_id' => $sr->id,
        'quantity_returned' => 5,
        'unit_price' => 60000,
    ]);

    putJson("/api/supplier-returns/{$sr->id}", [
        'status' => 'confirmed',
    ])->assertOk()->assertJsonPath('data.status', 'confirmed');

    $sr->refresh();
    expect($sr->journal_entry_id)->not->toBeNull();

    assertDatabaseHas('journal_entries', [
        'id' => $sr->journal_entry_id,
        'status' => 'posted',
        'journal_type' => 'system',
        'source_type' => SupplierReturn::class,
        'source_id' => $sr->id,
    ]);
});
