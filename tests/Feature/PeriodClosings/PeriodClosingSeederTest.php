<?php

use App\Actions\PeriodClosings\ClosePeriodAction;
use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\PeriodClosing;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('period-closings');

test('closed monthly period carries net income from posted journals', function () {
    $fiscalYear = FiscalYear::factory()->create([
        'name' => 'FY 2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);
    $coaVersion = CoaVersion::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'active',
    ]);
    $revenue = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'type' => 'revenue',
        'normal_balance' => 'credit',
    ]);
    $expense = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'type' => 'expense',
        'normal_balance' => 'debit',
    ]);

    $entry = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'entry_date' => '2026-01-15',
        'status' => 'posted',
    ]);
    $entry->lines()->create([
        'account_id' => $revenue->id,
        'credit' => 5000000,
        'debit' => 0,
    ]);
    $entry->lines()->create([
        'account_id' => $expense->id,
        'debit' => 500000,
        'credit' => 0,
    ]);

    $closing = PeriodClosing::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'period_year' => 2026,
        'period_month' => 1,
        'closing_type' => 'monthly',
        'status' => 'draft',
        'retained_earnings_account_id' => $revenue->id,
    ]);

    app(ClosePeriodAction::class)->execute($closing);

    $closing->refresh();

    expect($closing->status)->toBe('closed')
        ->and((float) $closing->net_income)->toBe(4500000.0);
});
