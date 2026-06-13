<?php

use App\Models\Account;
use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Services\BudgetVarianceService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('budgets');

function postLine(JournalEntry $entry, Account $account, float $debit, float $credit): void
{
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry->id,
        'account_id' => $account->id,
        'debit' => $debit,
        'credit' => $credit,
    ]);
}

function budgetLineFor(Account $account, FiscalYear $fiscalYear, float $allocated): BudgetLine
{
    $budget = Budget::factory()->create(['fiscal_year_id' => $fiscalYear->id]);

    return BudgetLine::factory()->create([
        'budget_id' => $budget->id,
        'account_id' => $account->id,
        'period_start' => '2026-01-01',
        'period_end' => '2026-01-31',
        'allocated_amount' => $allocated,
    ]);
}

test('expense account actual is debit minus credit', function () {
    $fiscalYear = FiscalYear::factory()->create();
    $account = Account::factory()->create(['type' => 'expense']);
    $line = budgetLineFor($account, $fiscalYear, 10000);

    $entry = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'posted',
        'entry_date' => '2026-01-15',
    ]);
    postLine($entry, $account, 3000, 500);

    $variance = (new BudgetVarianceService)->calculateVariance($line->budget);

    expect($variance->first()['actual'])->toBe(2500.0)
        ->and($variance->first()['available'])->toBe(7500.0)
        ->and($variance->first()['variance_percent'])->toBe(75.0)
        ->and($variance->first()['status'])->toBe('within_budget');
});

test('revenue account actual is credit minus debit', function () {
    $fiscalYear = FiscalYear::factory()->create();
    $account = Account::factory()->create(['type' => 'revenue']);
    $line = budgetLineFor($account, $fiscalYear, 10000);

    $entry = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'posted',
        'entry_date' => '2026-01-10',
    ]);
    postLine($entry, $account, 1000, 6000);

    $variance = (new BudgetVarianceService)->calculateVariance($line->budget);

    expect($variance->first()['actual'])->toBe(5000.0)
        ->and($variance->first()['available'])->toBe(5000.0);
});

test('asset account uses debit positive sign', function () {
    $fiscalYear = FiscalYear::factory()->create();
    $account = Account::factory()->create(['type' => 'asset']);
    $line = budgetLineFor($account, $fiscalYear, 8000);

    $entry = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'posted',
        'entry_date' => '2026-01-20',
    ]);
    postLine($entry, $account, 4000, 0);

    $variance = (new BudgetVarianceService)->calculateVariance($line->budget);

    expect($variance->first()['actual'])->toBe(4000.0);
});

test('liability and equity accounts use credit positive sign', function () {
    $fiscalYear = FiscalYear::factory()->create();

    $liability = Account::factory()->create(['type' => 'liability']);
    $liabilityLine = budgetLineFor($liability, $fiscalYear, 5000);
    $liabilityEntry = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'posted',
        'entry_date' => '2026-01-05',
    ]);
    postLine($liabilityEntry, $liability, 500, 2500);

    $equity = Account::factory()->create(['type' => 'equity']);
    $equityLine = budgetLineFor($equity, $fiscalYear, 5000);
    $equityEntry = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'posted',
        'entry_date' => '2026-01-06',
    ]);
    postLine($equityEntry, $equity, 0, 3000);

    $service = new BudgetVarianceService;

    expect($service->calculateVariance($liabilityLine->budget)->first()['actual'])->toBe(2000.0)
        ->and($service->calculateVariance($equityLine->budget)->first()['actual'])->toBe(3000.0);
});

test('only posted journal entries count toward actual', function () {
    $fiscalYear = FiscalYear::factory()->create();
    $account = Account::factory()->create(['type' => 'expense']);
    $line = budgetLineFor($account, $fiscalYear, 10000);

    $draft = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'draft',
        'entry_date' => '2026-01-15',
    ]);
    postLine($draft, $account, 9999, 0);

    $posted = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'posted',
        'entry_date' => '2026-01-16',
    ]);
    postLine($posted, $account, 1500, 0);

    $variance = (new BudgetVarianceService)->calculateVariance($line->budget);

    expect($variance->first()['actual'])->toBe(1500.0);
});

test('entries outside the period date range are excluded', function () {
    $fiscalYear = FiscalYear::factory()->create();
    $account = Account::factory()->create(['type' => 'expense']);
    $line = budgetLineFor($account, $fiscalYear, 10000);

    $inside = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'posted',
        'entry_date' => '2026-01-15',
    ]);
    postLine($inside, $account, 2000, 0);

    $outside = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'posted',
        'entry_date' => '2026-02-15',
    ]);
    postLine($outside, $account, 5000, 0);

    $variance = (new BudgetVarianceService)->calculateVariance($line->budget);

    expect($variance->first()['actual'])->toBe(2000.0);
});

test('entries from another fiscal year are excluded', function () {
    $fiscalYear = FiscalYear::factory()->create();
    $otherYear = FiscalYear::factory()->create();
    $account = Account::factory()->create(['type' => 'expense']);
    $line = budgetLineFor($account, $fiscalYear, 10000);

    $foreign = JournalEntry::factory()->create([
        'fiscal_year_id' => $otherYear->id,
        'status' => 'posted',
        'entry_date' => '2026-01-15',
    ]);
    postLine($foreign, $account, 7000, 0);

    $variance = (new BudgetVarianceService)->calculateVariance($line->budget);

    expect($variance->first()['actual'])->toBe(0.0);
});

test('status is warning when spent at least eighty percent', function () {
    $fiscalYear = FiscalYear::factory()->create();
    $account = Account::factory()->create(['type' => 'expense']);
    $line = budgetLineFor($account, $fiscalYear, 10000);

    $entry = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'posted',
        'entry_date' => '2026-01-15',
    ]);
    postLine($entry, $account, 8500, 0);

    $variance = (new BudgetVarianceService)->calculateVariance($line->budget);

    expect($variance->first()['status'])->toBe('warning');
});

test('status is over budget when actual exceeds allocation', function () {
    $fiscalYear = FiscalYear::factory()->create();
    $account = Account::factory()->create(['type' => 'expense']);
    $line = budgetLineFor($account, $fiscalYear, 10000);

    $entry = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'posted',
        'entry_date' => '2026-01-15',
    ]);
    postLine($entry, $account, 12000, 0);

    $variance = (new BudgetVarianceService)->calculateVariance($line->budget);

    expect($variance->first()['actual'])->toBe(12000.0)
        ->and($variance->first()['available'])->toBe(-2000.0)
        ->and($variance->first()['status'])->toBe('over_budget');
});

test('variance percent is null when allocation is zero', function () {
    $fiscalYear = FiscalYear::factory()->create();
    $account = Account::factory()->create(['type' => 'expense']);
    $line = budgetLineFor($account, $fiscalYear, 0);

    $variance = (new BudgetVarianceService)->calculateVariance($line->budget);

    expect($variance->first()['variance_percent'])->toBeNull()
        ->and($variance->first()['status'])->toBe('within_budget');
});

test('summary aggregates allocated actual and available totals', function () {
    $fiscalYear = FiscalYear::factory()->create();
    $budget = Budget::factory()->create(['fiscal_year_id' => $fiscalYear->id]);

    $expense = Account::factory()->create(['type' => 'expense']);
    BudgetLine::factory()->create([
        'budget_id' => $budget->id,
        'account_id' => $expense->id,
        'period_start' => '2026-01-01',
        'period_end' => '2026-01-31',
        'allocated_amount' => 10000,
    ]);
    $expenseEntry = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'posted',
        'entry_date' => '2026-01-15',
    ]);
    postLine($expenseEntry, $expense, 4000, 0);

    $otherExpense = Account::factory()->create(['type' => 'expense']);
    BudgetLine::factory()->create([
        'budget_id' => $budget->id,
        'account_id' => $otherExpense->id,
        'period_start' => '2026-01-01',
        'period_end' => '2026-01-31',
        'allocated_amount' => 6000,
    ]);
    $otherEntry = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'posted',
        'entry_date' => '2026-01-18',
    ]);
    postLine($otherEntry, $otherExpense, 2000, 0);

    $service = new BudgetVarianceService;
    $variance = $service->calculateVariance($budget);
    $summary = $service->calculateSummary($variance);

    expect($summary['total_allocated'])->toBe(16000.0)
        ->and($summary['total_actual'])->toBe(6000.0)
        ->and($summary['total_available'])->toBe(10000.0)
        ->and($summary['overall_variance_percent'])->toBe(62.5);
});

test('summary variance percent is null when nothing allocated', function () {
    $fiscalYear = FiscalYear::factory()->create();
    $budget = Budget::factory()->create(['fiscal_year_id' => $fiscalYear->id]);
    $account = Account::factory()->create(['type' => 'expense']);
    BudgetLine::factory()->create([
        'budget_id' => $budget->id,
        'account_id' => $account->id,
        'period_start' => '2026-01-01',
        'period_end' => '2026-01-31',
        'allocated_amount' => 0,
    ]);

    $service = new BudgetVarianceService;
    $summary = $service->calculateSummary($service->calculateVariance($budget));

    expect($summary['overall_variance_percent'])->toBeNull();
});
