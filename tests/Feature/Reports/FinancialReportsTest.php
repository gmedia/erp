<?php

namespace Tests\Feature\Reports;

use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use Carbon\Carbon;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\seed;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('reports');

beforeEach(function () {
    // seed(); // Avoid full seed to prevent conflicts/slowness
    
    // Create necessary data for testing
    $this->user = createTestUserWithPermissions(['trial_balance_report', 'balance_sheet_report', 'cash_flow_report']);
    
    // Create Fiscal Year
    $this->fiscalYear = FiscalYear::create([
        'name' => '2025',
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
        'status' => 'open',
    ]);

    // Create COA Version
    $this->coaVersion = CoaVersion::create([
        'name' => 'COA 2025',
        'fiscal_year_id' => $this->fiscalYear->id,
        'status' => 'active',
    ]);
});

test('can view trial balance page', function () {
    actingAs($this->user)
        ->get(route('reports.trial-balance'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('reports/trial-balance/index')
        );
});

test('can view balance sheet page', function () {
    actingAs($this->user)
        ->get(route('reports.balance-sheet'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('reports/balance-sheet/index')
        );
});

test('can view cash flow page', function () {
    actingAs($this->user)
        ->get(route('reports.cash-flow'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('reports/cash-flow/index')
        );
});

test('trial balance calculations are correct', function () {
    // Create Accounts
    $parentAccount = Account::create([
        'coa_version_id' => $this->coaVersion->id,
        'code' => '1000',
        'name' => 'Cash Parent',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'level' => 1,
        'is_active' => true,
        'is_cash_flow' => true,
    ]);

    $childAccount = Account::create([
        'coa_version_id' => $this->coaVersion->id,
        'code' => '1100',
        'name' => 'Cash Child',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'level' => 2,
        'parent_id' => $parentAccount->id,
        'is_active' => true,
        'is_cash_flow' => true,
    ]);

    $revenue = Account::create([
        'coa_version_id' => $this->coaVersion->id,
        'code' => '4000',
        'name' => 'Revenue',
        'type' => 'revenue',
        'normal_balance' => 'credit',
        'level' => 1,
        'is_active' => true,
        'is_cash_flow' => false,
    ]);

    // Create Journal Entry
    $journal = JournalEntry::create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'entry_number' => 'JV-001',
        'entry_date' => '2025-01-15',
        'description' => 'Test Transaction',
        'status' => 'posted',
        'created_by' => $this->user->id,
    ]);

    // Debit Child Cash 1000
    JournalEntryLine::create([
        'journal_entry_id' => $journal->id,
        'account_id' => $childAccount->id,
        'debit' => 1000,
        'credit' => 0,
    ]);

    // Credit Revenue 1000
    JournalEntryLine::create([
        'journal_entry_id' => $journal->id,
        'account_id' => $revenue->id,
        'debit' => 0,
        'credit' => 1000,
    ]);

    actingAs($this->user)
        ->get(route('reports.trial-balance', ['fiscal_year_id' => $this->fiscalYear->id]))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('reports/trial-balance/index')
            ->has('report', 3) // Check count
            ->where('report.0.code', '1000') // Parent
            ->where('report.0.level', 1) 
            ->where('report.1.code', '1100') // Child
            ->where('report.1.level', 2)
            ->where('report.1.parent_id', $parentAccount->id)
            ->where('report.1.debit', 1000)
        );
});

test('cash flow calculations are correct', function () {
    $cash = Account::create([
        'coa_version_id' => $this->coaVersion->id,
        'code' => '1100',
        'name' => 'Cash',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'level' => 1,
        'is_active' => true,
        'is_cash_flow' => false,
    ]);

    $revenueCash = Account::create([
        'coa_version_id' => $this->coaVersion->id,
        'code' => '4100',
        'name' => 'Cash Revenue',
        'type' => 'revenue',
        'normal_balance' => 'credit',
        'level' => 1,
        'is_active' => true,
        'is_cash_flow' => true,
    ]);

    $expenseCash = Account::create([
        'coa_version_id' => $this->coaVersion->id,
        'code' => '5200',
        'name' => 'Cash Expense',
        'type' => 'expense',
        'normal_balance' => 'debit',
        'level' => 1,
        'is_active' => true,
        'is_cash_flow' => true,
    ]);

    $journal1 = JournalEntry::create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'entry_number' => 'JV-100',
        'entry_date' => '2025-02-01',
        'description' => 'Cash sale',
        'status' => 'posted',
        'created_by' => $this->user->id,
    ]);

    JournalEntryLine::create([
        'journal_entry_id' => $journal1->id,
        'account_id' => $cash->id,
        'debit' => 1000,
        'credit' => 0,
    ]);

    JournalEntryLine::create([
        'journal_entry_id' => $journal1->id,
        'account_id' => $revenueCash->id,
        'debit' => 0,
        'credit' => 1000,
    ]);

    $journal2 = JournalEntry::create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'entry_number' => 'JV-101',
        'entry_date' => '2025-02-02',
        'description' => 'Cash expense',
        'status' => 'posted',
        'created_by' => $this->user->id,
    ]);

    JournalEntryLine::create([
        'journal_entry_id' => $journal2->id,
        'account_id' => $expenseCash->id,
        'debit' => 200,
        'credit' => 0,
    ]);

    JournalEntryLine::create([
        'journal_entry_id' => $journal2->id,
        'account_id' => $cash->id,
        'debit' => 0,
        'credit' => 200,
    ]);

    actingAs($this->user)
        ->get(route('reports.cash-flow', ['fiscal_year_id' => $this->fiscalYear->id]))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('reports/cash-flow/index')
            ->has('report', 2)
            ->where('report.0.code', '4100')
            ->where('report.0.inflow', 1000)
            ->where('report.1.code', '5200')
            ->where('report.1.outflow', 200)
        );
});

test('balance sheet accounts include net income in equity', function () {
    // Create Revenue/Expense accounts and transactions
    $revenue = Account::factory()->create([
        'coa_version_id' => $this->coaVersion->id,
        'code' => '4000',
        'name' => 'Sales',
        'type' => 'revenue',
        'normal_balance' => 'credit',
    ]);
    
    $expense = Account::factory()->create([
        'coa_version_id' => $this->coaVersion->id,
        'code' => '5000',
        'name' => 'Cost of Goods',
        'type' => 'expense',
        'normal_balance' => 'debit',
    ]);

    // Create journal entry: Credit Revenue 1000, Debit Expense 200
    // Net Income = 1000 - 200 = 800
    $je = JournalEntry::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'status' => 'posted',
    ]);

    JournalEntryLine::factory()->create([
        'journal_entry_id' => $je->id,
        'account_id' => $revenue->id,
        'credit' => 1000,
        'debit' => 0,
    ]);

    JournalEntryLine::factory()->create([
        'journal_entry_id' => $je->id,
        'account_id' => $expense->id,
        'debit' => 200,
        'credit' => 0,
    ]);

    actingAs($this->user)
        ->get(route('reports.balance-sheet', ['fiscal_year_id' => $this->fiscalYear->id]))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->where('report.totals.equity', 800) // Assumes no other equity
        );
});

test('balance sheet comparison works', function () {
    // Create Previous Fiscal Year
    $prevFiscalYear = FiscalYear::factory()->create([
        'name' => 'FY-2024',
        'start_date' => Carbon::now()->subYear()->startOfYear(),
        'end_date' => Carbon::now()->subYear()->endOfYear(),
        'status' => 'closed',
    ]);
    
    $prevCoaVersion = CoaVersion::factory()->create([
        'name' => 'v1-Prev', 
        'status' => 'active',
        'fiscal_year_id' => $prevFiscalYear->id
    ]);
    // $prevFiscalYear->coaVersions()->attach($prevCoaVersion);

    // Create Asset Account in Prev COA matching Current COA Code
    $assetAccount = Account::factory()->create([
        'coa_version_id' => $this->coaVersion->id,
        'code' => '1000', // Matches
        'type' => 'asset',
        'normal_balance' => 'debit',
        'name' => 'Cash'
    ]);

    $prevAssetAccount = Account::factory()->create([
        'coa_version_id' => $prevCoaVersion->id,
        'code' => '1000', // SAME CODE
        'type' => 'asset',
        'normal_balance' => 'debit',
        'name' => 'Cash Prev'
    ]);

    // Current Year txn
    $jeCurr = JournalEntry::factory()->create(['fiscal_year_id' => $this->fiscalYear->id, 'status' => 'posted']);
    JournalEntryLine::factory()->create(['journal_entry_id' => $jeCurr->id, 'account_id' => $assetAccount->id, 'debit' => 500, 'credit' => 0]);

    // Prev Year txn
    $jePrev = JournalEntry::factory()->create(['fiscal_year_id' => $prevFiscalYear->id, 'status' => 'posted']);
    JournalEntryLine::factory()->create(['journal_entry_id' => $jePrev->id, 'account_id' => $prevAssetAccount->id, 'debit' => 300, 'credit' => 0]);

    actingAs($this->user)
        ->get(route('reports.balance-sheet', ['fiscal_year_id' => $this->fiscalYear->id, 'comparison_year_id' => $prevFiscalYear->id]))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->where('report.totals.assets', 500)
            ->where('report.totals.comparison_assets', 300)
            ->where('report.totals.change_assets', 200) // 500 - 300
            ->where('report.totals.change_percentage_assets', 66.66666666666666) // (200/300)*100
        );
});
