<?php

use App\Models\FiscalYear;
use App\Models\RecurringJournal;
use App\Models\RecurringJournalLine;
use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\JournalEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\artisan;

uses(RefreshDatabase::class)->group('recurring-journal-scheduler');

function makeSchedulerAccounts(): array
{
    $coaVersion = CoaVersion::factory()->create(['status' => 'active']);

    $debitAccount = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '52000',
        'name' => 'Operating Expense',
        'type' => 'expense',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);

    $creditAccount = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '21100',
        'name' => 'Accounts Payable',
        'type' => 'liability',
        'normal_balance' => 'credit',
        'is_active' => true,
    ]);

    return ['debit' => $debitAccount, 'credit' => $creditAccount];
}

test('recurring-journals:execute processes due journals', function () {
    FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);
    $accounts = makeSchedulerAccounts();

    $journal = RecurringJournal::factory()->create([
        'name' => 'Monthly Rent',
        'frequency' => 'monthly',
        'is_active' => true,
        'next_run_date' => now()->subDay(),
        'end_date' => null,
        'total_amount' => 1000000,
        'auto_post' => true,
    ]);

    RecurringJournalLine::factory()->create([
        'recurring_journal_id' => $journal->id,
        'account_id' => $accounts['debit']->id,
        'debit' => 1000000,
        'credit' => 0,
    ]);

    RecurringJournalLine::factory()->create([
        'recurring_journal_id' => $journal->id,
        'account_id' => $accounts['credit']->id,
        'debit' => 0,
        'credit' => 1000000,
    ]);

    artisan('recurring-journals:execute')->assertSuccessful();

    expect(JournalEntry::where('source_type', RecurringJournal::class)->count())->toBe(1);

    $journal->refresh();
    expect($journal->last_run_date->toDateString())->toBe(now()->toDateString());
    expect($journal->next_run_date->gt(now()))->toBeTrue();
});

test('recurring-journals:execute skips inactive journals', function () {
    FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);

    RecurringJournal::factory()->create([
        'is_active' => false,
        'next_run_date' => now()->subDay(),
    ]);

    artisan('recurring-journals:execute')->assertSuccessful();

    expect(JournalEntry::count())->toBe(0);
});

test('recurring-journals:execute skips journals not yet due', function () {
    FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);

    RecurringJournal::factory()->create([
        'is_active' => true,
        'next_run_date' => now()->addWeek(),
    ]);

    artisan('recurring-journals:execute')->assertSuccessful();

    expect(JournalEntry::count())->toBe(0);
});
