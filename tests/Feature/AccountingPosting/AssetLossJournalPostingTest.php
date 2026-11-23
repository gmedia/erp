<?php

use App\Actions\AccountingPosting\PostAssetLossJournalAction;
use App\Models\Account;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Branch;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class)->group('asset-loss-journal-posting');

/**
 * @return array{equipment: Account, accumulated: Account, loss: Account}
 */
function makeAssetLossAccounts(): array
{
    $coaVersion = CoaVersion::factory()->create(['status' => 'active']);

    $make = fn (string $code, string $name, string $type, string $normal): Account => Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => $code,
        'name' => $name,
        'type' => $type,
        'normal_balance' => $normal,
        'is_active' => true,
    ]);

    return [
        'equipment' => $make('12100', 'Equipment', 'asset', 'debit'),
        'accumulated' => $make('12200', 'Accumulated Depreciation - Equipment', 'asset', 'credit'),
        'loss' => $make('52000', 'Operating Expense', 'expense', 'debit'),
    ];
}

function makeLostAsset(array $accounts, string $status = 'lost', ?string $fiscalStatus = 'open'): Asset
{
    FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => $fiscalStatus,
    ]);

    return Asset::factory()->create([
        'asset_category_id' => AssetCategory::factory()->create()->id,
        'branch_id' => Branch::factory()->create()->id,
        'asset_code' => 'FA-000008',
        'name' => 'Kamera Lapangan',
        'status' => $status,
        'purchase_date' => '2026-01-05',
        'purchase_cost' => 12000000,
        'salvage_value' => 0,
        'depreciation_method' => 'straight_line',
        'depreciation_start_date' => '2026-01-05',
        'useful_life_months' => 60,
        'accumulated_depreciation' => 2400000,
        'book_value' => 9600000,
        'depreciation_expense_account_id' => $accounts['loss']->id,
        'accumulated_depr_account_id' => $accounts['accumulated']->id,
    ]);
}

it('posts book value as a loss and zeroes the asset and accumulated accounts', function () {
    $accounts = makeAssetLossAccounts();
    $asset = makeLostAsset($accounts);

    $entry = app(PostAssetLossJournalAction::class)->execute($asset);

    expect($entry->status)->toBe('posted')
        ->and($entry->journal_type)->toBe('system')
        ->and($entry->reference)->toBe('LOSS-FA-000008');

    $lines = JournalEntryLine::query()->where('journal_entry_id', $entry->id)->get()->keyBy('account_id');

    // Nilai buku 9.600.000 = rugi. Akun aset dan akumulasi dinolkan.
    expect((float) $lines[$accounts['loss']->id]->debit)->toBe(9600000.0)
        ->and((float) $lines[$accounts['accumulated']->id]->debit)->toBe(2400000.0)
        ->and((float) $lines[$accounts['equipment']->id]->credit)->toBe(12000000.0);

    $debit = (float) JournalEntryLine::where('journal_entry_id', $entry->id)->sum('debit');
    $credit = (float) JournalEntryLine::where('journal_entry_id', $entry->id)->sum('credit');
    expect($debit)->toBe($credit);
});

it('is idempotent per asset', function () {
    $accounts = makeAssetLossAccounts();
    $asset = makeLostAsset($accounts);

    $first = app(PostAssetLossJournalAction::class)->execute($asset);
    $second = app(PostAssetLossJournalAction::class)->execute($asset);

    expect($second->id)->toBe($first->id)
        ->and(JournalEntry::where('reference', 'LOSS-FA-000008')->count())->toBe(1);
});

it('rejects posting into a closed fiscal period with 422', function () {
    $accounts = makeAssetLossAccounts();
    $asset = makeLostAsset($accounts, 'lost', 'closed');

    expect(fn () => app(PostAssetLossJournalAction::class)->execute($asset))
        ->toThrow(ValidationException::class);

    expect(JournalEntry::where('reference', 'LOSS-FA-000008')->exists())->toBeFalse();
});

it('does nothing for assets that are not lost', function () {
    $accounts = makeAssetLossAccounts();
    $asset = makeLostAsset($accounts, 'active');

    expect(app(PostAssetLossJournalAction::class)->isPostable($asset))->toBeFalse();
});
