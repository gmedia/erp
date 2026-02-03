<?php

use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\seed;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // seed(); // Avoid full seed to prevent conflicts/slowness
    
    // Create necessary data for testing
    // Ensure permission exists if we were using it, but currently no permission middleware on reports
    // Permission::firstOrCreate(['name' => 'view_reports']); 

    $this->user = User::factory()->create();
    // $this->user->givePermissionTo('view_reports'); 
    
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

test('trial balance calculations are correct', function () {
    // Create Accounts
    $cash = Account::create([
        'coa_version_id' => $this->coaVersion->id,
        'code' => '1000',
        'name' => 'Cash',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'level' => 1,
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

    // Debit Cash 1000
    JournalEntryLine::create([
        'journal_entry_id' => $journal->id,
        'account_id' => $cash->id,
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
            ->where('report.0.code', '1000')
            ->where('report.0.debit', 1000)
            ->where('report.1.code', '4000')
            ->where('report.1.credit', 1000)
        );
});

test('balance sheet includes net income in equity', function () {
    // Create Accounts
    $cash = Account::create([
        'coa_version_id' => $this->coaVersion->id,
        'code' => '1000',
        'name' => 'Cash',
        'type' => 'asset', // Asset
        'normal_balance' => 'debit',
        'level' => 1,
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

     $expense = Account::create([
        'coa_version_id' => $this->coaVersion->id,
        'code' => '5000',
        'name' => 'Expense',
        'type' => 'expense',
        'normal_balance' => 'debit',
        'level' => 1,
        'is_active' => true,
        'is_cash_flow' => false,
    ]);

    // Create Journal Entry
    $journal = JournalEntry::create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'entry_number' => 'JV-001',
        'entry_date' => '2025-01-15',
        'description' => 'Sales',
        'status' => 'posted',
        'created_by' => $this->user->id,
    ]);

    // Debit Cash 1000, Credit Revenue 1000
    JournalEntryLine::create(['journal_entry_id' => $journal->id, 'account_id' => $cash->id, 'debit' => 1000, 'credit' => 0]);
    JournalEntryLine::create(['journal_entry_id' => $journal->id, 'account_id' => $revenue->id, 'debit' => 0, 'credit' => 1000]);

    // Create Expense Entry
    $journal2 = JournalEntry::create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'entry_number' => 'JV-002',
        'entry_date' => '2025-01-20',
        'description' => 'Expense',
        'status' => 'posted',
        'created_by' => $this->user->id,
    ]);

    // Debit Expense 200, Credit Cash 200
    JournalEntryLine::create(['journal_entry_id' => $journal2->id, 'account_id' => $expense->id, 'debit' => 200, 'credit' => 0]);
    JournalEntryLine::create(['journal_entry_id' => $journal2->id, 'account_id' => $cash->id, 'debit' => 0, 'credit' => 200]);

    // Net Income should be 1000 - 200 = 800
    // Cash Balance: 1000 - 200 = 800 (Asset)
    // Equity should show 800 (Current Year Earnings)

    actingAs($this->user)
        ->get(route('reports.balance-sheet', ['fiscal_year_id' => $this->fiscalYear->id]))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->where('report.totals.assets', 800)
            ->where('report.totals.equity', 800) // Assumes no other equity
        );
});
