<?php

use App\Actions\AccountingPosting\PostApPaymentJournalAction;
use App\Actions\AccountingPosting\PostGoodsReceiptJournalAction;
use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\Account;
use App\Models\ApPayment;
use App\Models\Branch;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\JournalEntry;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class)->group('journal-entries');

function makeBranchWritePathFiscalYear(): FiscalYear
{
    return FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);
}

function makeBranchWritePathAccounts(): array
{
    $coaVersion = CoaVersion::factory()->create(['status' => 'active']);

    return [
        'ap' => Account::factory()->create([
            'coa_version_id' => $coaVersion->id,
            'code' => '21100',
            'name' => 'Accounts Payable',
            'type' => 'liability',
            'normal_balance' => 'credit',
            'is_active' => true,
        ]),
        'inventory' => Account::factory()->create([
            'coa_version_id' => $coaVersion->id,
            'code' => '11300',
            'name' => 'Inventory',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]),
        'bank' => Account::factory()->create([
            'coa_version_id' => $coaVersion->id,
            'code' => '11110',
            'name' => 'Cash in Bank',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]),
    ];
}

it('keeps branch_id in the JournalEntry fillable contract', function () {
    expect((new JournalEntry)->getFillable())->toContain('branch_id');
});

it('persists branch_id through mass assignment in CreateJournalEntryAction', function () {
    makeBranchWritePathFiscalYear();
    $accounts = makeBranchWritePathAccounts();
    $branch = Branch::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $entry = app(CreateJournalEntryAction::class)->execute([
        'entry_date' => '2026-03-15',
        'description' => 'Manual branch entry',
        'status' => 'posted',
        'branch_id' => $branch->id,
        'lines' => [
            ['account_id' => $accounts['bank']->id, 'debit' => 1000, 'credit' => 0],
            ['account_id' => $accounts['ap']->id, 'debit' => 0, 'credit' => 1000],
        ],
    ]);

    expect($entry->fresh()->branch_id)->toBe($branch->id);
});

it('defaults branch_id to null when not provided', function () {
    makeBranchWritePathFiscalYear();
    $accounts = makeBranchWritePathAccounts();
    Sanctum::actingAs(User::factory()->create());

    $entry = app(CreateJournalEntryAction::class)->execute([
        'entry_date' => '2026-03-15',
        'description' => 'Company-wide entry',
        'status' => 'posted',
        'lines' => [
            ['account_id' => $accounts['bank']->id, 'debit' => 1000, 'credit' => 0],
            ['account_id' => $accounts['ap']->id, 'debit' => 0, 'credit' => 1000],
        ],
    ]);

    expect($entry->fresh()->branch_id)->toBeNull();
});

it('posts a direct-branch source with its own branch_id', function () {
    makeBranchWritePathFiscalYear();
    makeBranchWritePathAccounts();
    $branch = Branch::factory()->create();

    $payment = ApPayment::factory()->confirmed()->create([
        'branch_id' => $branch->id,
        'payment_date' => '2026-03-15',
        'total_amount' => 750000,
        'journal_entry_id' => null,
    ]);

    $entry = app(PostApPaymentJournalAction::class)->execute($payment->fresh());

    expect($entry)->not->toBeNull()
        ->and($entry->branch_id)->toBe($branch->id);
});

it('posts a warehouse-based source with the warehouse branch_id', function () {
    makeBranchWritePathFiscalYear();
    makeBranchWritePathAccounts();
    $branch = Branch::factory()->create();
    $warehouse = Warehouse::factory()->create(['branch_id' => $branch->id]);

    $gr = GoodsReceipt::factory()->create([
        'status' => 'confirmed',
        'warehouse_id' => $warehouse->id,
        'receipt_date' => '2026-03-15',
        'journal_entry_id' => null,
        'confirmed_by' => User::factory()->create()->id,
        'confirmed_at' => now(),
    ]);
    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr->id,
        'quantity_accepted' => 10,
        'unit_price' => 50000,
    ]);

    $entry = app(PostGoodsReceiptJournalAction::class)->execute($gr->fresh());

    expect($entry)->not->toBeNull()
        ->and($entry->branch_id)->toBe($branch->id);
});

it('leaves branch_id null when the source warehouse has no branch', function () {
    makeBranchWritePathFiscalYear();
    makeBranchWritePathAccounts();
    $warehouse = Warehouse::factory()->create(['branch_id' => null]);

    $gr = GoodsReceipt::factory()->create([
        'status' => 'confirmed',
        'warehouse_id' => $warehouse->id,
        'receipt_date' => '2026-03-15',
        'journal_entry_id' => null,
        'confirmed_by' => User::factory()->create()->id,
        'confirmed_at' => now(),
    ]);
    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr->id,
        'quantity_accepted' => 10,
        'unit_price' => 50000,
    ]);

    $entry = app(PostGoodsReceiptJournalAction::class)->execute($gr->fresh());

    expect($entry)->not->toBeNull()
        ->and($entry->branch_id)->toBeNull();
});
