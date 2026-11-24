<?php

use App\Actions\RecurringJournals\ExecuteRecurringJournalAction;
use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\PeriodClosing;
use App\Models\RecurringJournal;
use App\Models\RecurringJournalLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\artisan;

uses(RefreshDatabase::class)->group('recurring-journal-scheduler');

function guardOpenFiscalYear(string $status = 'open'): FiscalYear
{
    $year = now()->year;

    return FiscalYear::factory()->create([
        'name' => (string) $year,
        'start_date' => now()->startOfYear()->toDateString(),
        'end_date' => now()->endOfYear()->toDateString(),
        'status' => $status,
    ]);
}

function guardAccounts(): array
{
    $coaVersion = CoaVersion::factory()->create(['status' => 'active']);

    $debit = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '52000',
        'name' => 'Operating Expense',
        'type' => 'expense',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);

    $credit = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '21100',
        'name' => 'Accounts Payable',
        'type' => 'liability',
        'normal_balance' => 'credit',
        'is_active' => true,
    ]);

    return ['debit' => $debit, 'credit' => $credit];
}

function guardDueJournal(
    FiscalYear $fiscalYear,
    Account $debit,
    Account $credit,
    array $attributes = [],
): RecurringJournal {
    $journal = RecurringJournal::factory()->create(array_merge([
        'name' => 'Monthly Rent',
        'fiscal_year_id' => $fiscalYear->id,
        'frequency' => 'monthly',
        'is_active' => true,
        'next_run_date' => now()->subDay(),
        'last_run_date' => null,
        'end_date' => null,
        'total_amount' => 1000000,
        'auto_post' => false,
    ], $attributes));

    RecurringJournalLine::factory()->create([
        'recurring_journal_id' => $journal->id,
        'account_id' => $debit->id,
        'debit' => 1000000,
        'credit' => 0,
    ]);

    RecurringJournalLine::factory()->create([
        'recurring_journal_id' => $journal->id,
        'account_id' => $credit->id,
        'debit' => 0,
        'credit' => 1000000,
    ]);

    return $journal;
}

function guardClosedMonth(FiscalYear $fiscalYear): void
{
    PeriodClosing::factory()->closed()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'closing_type' => 'monthly',
        'period_year' => now()->year,
        'period_month' => now()->month,
    ]);
}

test('recurring journal entry number keeps the RJ prefix with fiscal sequence', function () {
    $fiscalYear = guardOpenFiscalYear();
    $accounts = guardAccounts();
    $journal = guardDueJournal($fiscalYear, $accounts['debit'], $accounts['credit'], ['auto_post' => true]);

    $entry = app(ExecuteRecurringJournalAction::class)->execute($journal);

    expect($entry->entry_number)->toMatch('/^RJ-' . now()->year . '-\d{5}$/');
    expect($entry->status)->toBe('posted');
});

test('draft recurring journal is rejected when the posting period is closed', function () {
    $fiscalYear = guardOpenFiscalYear();
    guardClosedMonth($fiscalYear);
    $accounts = guardAccounts();
    $journal = guardDueJournal($fiscalYear, $accounts['debit'], $accounts['credit'], ['auto_post' => false]);

    expect(fn () => app(ExecuteRecurringJournalAction::class)->execute($journal))
        ->toThrow(ValidationException::class);

    expect(JournalEntry::count())->toBe(0);

    $journal->refresh();
    expect($journal->last_run_date)->toBeNull();
    expect($journal->next_run_date->toDateString())->toBe(now()->subDay()->toDateString());
});

test('posted recurring journal is rejected when the posting period is closed', function () {
    $fiscalYear = guardOpenFiscalYear();
    guardClosedMonth($fiscalYear);
    $accounts = guardAccounts();
    $journal = guardDueJournal($fiscalYear, $accounts['debit'], $accounts['credit'], ['auto_post' => true]);

    expect(fn () => app(ExecuteRecurringJournalAction::class)->execute($journal))
        ->toThrow(ValidationException::class);

    expect(JournalEntry::count())->toBe(0);
    expect($journal->refresh()->last_run_date)->toBeNull();
});

test('draft recurring journal is rejected when the fiscal year is closed', function () {
    $fiscalYear = guardOpenFiscalYear('closed');
    $accounts = guardAccounts();
    $journal = guardDueJournal($fiscalYear, $accounts['debit'], $accounts['credit'], ['auto_post' => false]);

    expect(fn () => app(ExecuteRecurringJournalAction::class)->execute($journal))
        ->toThrow(ValidationException::class);

    expect(JournalEntry::count())->toBe(0);
    expect($journal->refresh()->last_run_date)->toBeNull();
});

test('draft recurring journal is rejected when a line account is inactive', function () {
    $fiscalYear = guardOpenFiscalYear();
    $accounts = guardAccounts();
    $accounts['debit']->update(['is_active' => false]);
    $journal = guardDueJournal($fiscalYear, $accounts['debit'], $accounts['credit'], ['auto_post' => false]);

    expect(fn () => app(ExecuteRecurringJournalAction::class)->execute($journal))
        ->toThrow(ValidationException::class);

    expect(JournalEntry::count())->toBe(0);
    expect($journal->refresh()->next_run_date->toDateString())->toBe(now()->subDay()->toDateString());
});

test('scheduler keeps running other journals when one fails and retries it on the next run', function () {
    $fiscalYear = guardOpenFiscalYear();
    $accounts = guardAccounts();

    $inactiveDebit = Account::factory()->create([
        'coa_version_id' => $accounts['debit']->coa_version_id,
        'code' => '52999',
        'name' => 'Inactive Expense',
        'type' => 'expense',
        'normal_balance' => 'debit',
        'is_active' => false,
    ]);

    $failing = guardDueJournal($fiscalYear, $inactiveDebit, $accounts['credit'], [
        'name' => 'Failing Journal',
        'next_run_date' => now()->subDays(2),
    ]);

    $passing = guardDueJournal($fiscalYear, $accounts['debit'], $accounts['credit'], [
        'name' => 'Passing Journal',
        'next_run_date' => now()->subDay(),
    ]);

    artisan('recurring-journals:execute')->assertExitCode(1);

    expect(JournalEntry::count())->toBe(1);
    expect($passing->refresh()->last_run_date->toDateString())->toBe(now()->toDateString());
    expect($failing->refresh()->last_run_date)->toBeNull();
    expect($failing->next_run_date->toDateString())->toBe(now()->subDays(2)->toDateString());

    $inactiveDebit->update(['is_active' => true]);

    artisan('recurring-journals:execute')->assertSuccessful();

    expect(JournalEntry::count())->toBe(2);
    expect($failing->refresh()->last_run_date->toDateString())->toBe(now()->toDateString());
});
