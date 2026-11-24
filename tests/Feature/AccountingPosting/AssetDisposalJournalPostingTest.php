<?php

use App\Actions\AccountingPosting\PostAssetDisposalJournalAction;
use App\Models\Account;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Branch;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class)->group('asset-disposal-journal');

/**
 * Membuat COA aktif (tahun fiskal 2026 terbuka) dan akun pelepasan aset.
 *
 * @return array{equipment: Account, accumulated: Account, loss: Account}
 */
function makeDisposalAccounts(): array
{
    $fiscalYear = FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);
    $coaVersion = CoaVersion::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'active',
    ]);

    $make = fn (string $code, string $name, string $type, string $normal): Account => Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => $code,
        'name' => $name,
        'type' => $type,
        'normal_balance' => $normal,
        'is_active' => true,
    ]);

    $accounts = [
        'equipment' => $make('12100', 'Equipment', 'asset', 'debit'),
        'accumulated' => $make('12200', 'Accumulated Depreciation - Equipment', 'asset', 'credit'),
        'loss' => $make('52000', 'Loss on Disposal of Assets', 'expense', 'debit'),
    ];

    Setting::set('asset_disposal_loss_account_id', (string) $accounts['loss']->id);

    return $accounts;
}

function makeDisposedAsset(
    array $accounts,
    string $status = 'disposed',
    string $code = 'FA-000021',
    float $accumulated = 2400000,
): Asset {
    return Asset::factory()->create([
        'asset_category_id' => AssetCategory::factory()->create()->id,
        'branch_id' => Branch::factory()->create()->id,
        'asset_code' => $code,
        'name' => 'Mesin Fotokopi',
        'status' => $status,
        'purchase_date' => '2026-01-05',
        'purchase_cost' => 12000000,
        'salvage_value' => 0,
        'depreciation_method' => 'straight_line',
        'depreciation_start_date' => '2026-01-05',
        'useful_life_months' => 60,
        'accumulated_depreciation' => $accumulated,
        'book_value' => 12000000 - $accumulated,
        'depreciation_expense_account_id' => $accounts['loss']->id,
        'accumulated_depr_account_id' => $accounts['accumulated']->id,
    ]);
}

it('posts a disposal journal with book value as loss and zeroes asset and accumulated accounts', function () {
    $accounts = makeDisposalAccounts();
    $asset = makeDisposedAsset($accounts);

    $entry = app(PostAssetDisposalJournalAction::class)->execute($asset, '2026-06-30');

    expect($entry->status)->toBe('posted')
        ->and($entry->journal_type)->toBe('system')
        ->and($entry->reference)->toBe('DISP-FA-000021')
        ->and($entry->entry_date->toDateString())->toBe('2026-06-30');

    $lines = JournalEntryLine::query()->where('journal_entry_id', $entry->id)->get()->keyBy('account_id');

    expect((float) $lines[$accounts['accumulated']->id]->debit)->toBe(2400000.0)
        ->and((float) $lines[$accounts['loss']->id]->debit)->toBe(9600000.0)
        ->and((float) $lines[$accounts['equipment']->id]->credit)->toBe(12000000.0);

    $debit = (float) JournalEntryLine::where('journal_entry_id', $entry->id)->sum('debit');
    $credit = (float) JournalEntryLine::where('journal_entry_id', $entry->id)->sum('credit');
    expect($debit)->toBe($credit);
});

it('omits the loss line when the asset is fully depreciated', function () {
    $accounts = makeDisposalAccounts();
    $asset = makeDisposedAsset($accounts, 'lost', 'FA-000023', 12000000);

    $entry = app(PostAssetDisposalJournalAction::class)->execute($asset, '2026-06-30');

    $lines = JournalEntryLine::query()->where('journal_entry_id', $entry->id)->get();

    expect($lines->where('account_id', $accounts['loss']->id))->toHaveCount(0)
        ->and((float) $lines->sum('debit'))->toBe((float) $lines->sum('credit'));
});

it('is idempotent per asset so a disposal is journaled only once', function () {
    $accounts = makeDisposalAccounts();
    $asset = makeDisposedAsset($accounts);
    $action = app(PostAssetDisposalJournalAction::class);

    $first = $action->execute($asset, '2026-06-30');
    $second = $action->execute($asset, '2026-06-30');

    expect($second->id)->toBe($first->id)
        ->and(JournalEntry::where('reference', 'DISP-FA-000021')->count())->toBe(1);
});

it('rejects posting when the loss account is not configured', function () {
    $accounts = makeDisposalAccounts();
    Setting::set('asset_disposal_loss_account_id', '');
    $asset = makeDisposedAsset($accounts);

    expect(fn () => app(PostAssetDisposalJournalAction::class)->execute($asset, '2026-06-30'))
        ->toThrow(ValidationException::class);

    expect(JournalEntry::where('reference', 'DISP-FA-000021')->exists())->toBeFalse();
});

it('rejects posting into a closed fiscal period', function () {
    $accounts = makeDisposalAccounts();
    FiscalYear::where('name', '2026')->update(['status' => 'closed']);
    $asset = makeDisposedAsset($accounts);

    expect(fn () => app(PostAssetDisposalJournalAction::class)->execute($asset, '2026-06-30'))
        ->toThrow(ValidationException::class);

    expect(JournalEntry::where('reference', 'DISP-FA-000021')->exists())->toBeFalse();
});

it('does not journal assets that are not disposed or lost', function () {
    $accounts = makeDisposalAccounts();
    $asset = makeDisposedAsset($accounts, 'active', 'FA-000022');

    expect(app(PostAssetDisposalJournalAction::class)->isPostable($asset))->toBeFalse();
});
