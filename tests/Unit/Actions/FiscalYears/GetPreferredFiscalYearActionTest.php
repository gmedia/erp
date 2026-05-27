<?php

use App\Actions\FiscalYears\GetPreferredFiscalYearAction;
use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('fiscal-years');

/**
 * @return Collection<int, FiscalYear>
 */
function fiscalYearsOrderedByStartDateDesc(): Collection
{
    return FiscalYear::orderBy('start_date', 'desc')->get();
}

function postEntryFor(FiscalYear $fiscalYear): JournalEntry
{
    /** @var CoaVersion $coaVersion */
    $coaVersion = CoaVersion::factory()->create(['fiscal_year_id' => $fiscalYear->id]);
    /** @var Account $debit */
    $debit = Account::factory()->create(['coa_version_id' => $coaVersion->id]);
    /** @var Account $credit */
    $credit = Account::factory()->create(['coa_version_id' => $coaVersion->id]);

    /** @var JournalEntry $entry */
    $entry = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'posted',
        'created_by' => User::factory(),
    ]);

    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry->id,
        'account_id' => $debit->id,
        'debit' => 100,
        'credit' => 0,
    ]);

    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry->id,
        'account_id' => $credit->id,
        'debit' => 0,
        'credit' => 100,
    ]);

    return $entry;
}

test('it returns null when there are no fiscal years', function () {
    $action = new GetPreferredFiscalYearAction;

    expect($action->execute(fiscalYearsOrderedByStartDateDesc()))->toBeNull();
});

test('it prefers the latest fiscal year that has at least one posted journal entry', function () {
    $oldWithPosted = FiscalYear::factory()->create([
        'name' => '2024',
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
        'status' => 'closed',
    ]);
    FiscalYear::factory()->create([
        'name' => '2025',
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
        'status' => 'open',
    ]);
    FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);

    postEntryFor($oldWithPosted);

    $action = new GetPreferredFiscalYearAction;

    expect($action->execute(fiscalYearsOrderedByStartDateDesc())->id)->toBe($oldWithPosted->id);
});

test('it picks the latest posted fiscal year when multiple fiscal years have posted entries', function () {
    FiscalYear::factory()->create([
        'name' => '2023',
        'start_date' => '2023-01-01',
        'end_date' => '2023-12-31',
        'status' => 'closed',
    ]);
    $middle = FiscalYear::factory()->create([
        'name' => '2024',
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
        'status' => 'closed',
    ]);
    $latestPosted = FiscalYear::factory()->create([
        'name' => '2025',
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
        'status' => 'open',
    ]);
    FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);

    postEntryFor($middle);
    postEntryFor($latestPosted);

    $action = new GetPreferredFiscalYearAction;

    expect($action->execute(fiscalYearsOrderedByStartDateDesc())->id)->toBe($latestPosted->id);
});

test('it falls back to the first open fiscal year when none have posted entries', function () {
    FiscalYear::factory()->create([
        'name' => '2024',
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
        'status' => 'closed',
    ]);
    $openFiscalYear = FiscalYear::factory()->create([
        'name' => '2025',
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
        'status' => 'open',
    ]);
    FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'closed',
    ]);

    $action = new GetPreferredFiscalYearAction;

    expect($action->execute(fiscalYearsOrderedByStartDateDesc())->id)->toBe($openFiscalYear->id);
});

test('it falls back to the first fiscal year when none are open and none have posted entries', function () {
    $latest = FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'closed',
    ]);
    FiscalYear::factory()->create([
        'name' => '2025',
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
        'status' => 'closed',
    ]);

    $action = new GetPreferredFiscalYearAction;

    expect($action->execute(fiscalYearsOrderedByStartDateDesc())->id)->toBe($latest->id);
});

test('it ignores draft and void entries when scoring fiscal years', function () {
    $withOnlyDraft = FiscalYear::factory()->create([
        'name' => '2024',
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
        'status' => 'closed',
    ]);
    $openWithoutEntries = FiscalYear::factory()->create([
        'name' => '2025',
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
        'status' => 'open',
    ]);

    /** @var CoaVersion $coaVersion */
    $coaVersion = CoaVersion::factory()->create(['fiscal_year_id' => $withOnlyDraft->id]);
    $account = Account::factory()->create(['coa_version_id' => $coaVersion->id]);

    JournalEntry::factory()->create([
        'fiscal_year_id' => $withOnlyDraft->id,
        'status' => 'draft',
        'created_by' => User::factory(),
    ]);
    JournalEntry::factory()->create([
        'fiscal_year_id' => $withOnlyDraft->id,
        'status' => 'void',
        'created_by' => User::factory(),
    ]);

    expect($account)->not->toBeNull();

    $action = new GetPreferredFiscalYearAction;

    expect($action->execute(fiscalYearsOrderedByStartDateDesc())->id)->toBe($openWithoutEntries->id);
});
