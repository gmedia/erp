<?php

use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\PeriodClosing;
use Database\Seeders\GlExtendedSampleDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('period-closings');

test('seeded closed periods carry net income from posted journals', function () {
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

    $this->seed(GlExtendedSampleDataSeeder::class);

    $closing = PeriodClosing::query()
        ->where('fiscal_year_id', $fiscalYear->id)
        ->where('period_month', 1)
        ->first();

    expect($closing)->not->toBeNull()
        ->and((float) $closing->net_income)->toBe(4500000.0);
});
