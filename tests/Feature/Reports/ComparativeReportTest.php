<?php

namespace Tests\Feature\Reports;

use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use function Pest\Laravel\actingAs;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('reports');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['comparative_report']);

    $this->fyPrev = FiscalYear::create([
        'name' => '2025',
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
        'status' => 'closed',
    ]);

    $this->fyCurr = FiscalYear::create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);

    $this->coaPrev = CoaVersion::create([
        'name' => 'COA 2025',
        'fiscal_year_id' => $this->fyPrev->id,
        'status' => 'archived',
    ]);

    $this->coaCurr = CoaVersion::create([
        'name' => 'COA 2026',
        'fiscal_year_id' => $this->fyCurr->id,
        'status' => 'active',
    ]);
});

test('can view comparative report page', function () {
    actingAs($this->user)
        ->get(route('reports.comparative'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('reports/comparative/index'));
});

test('comparative uses archived previous year and mapping split allocated to LCA', function () {
    $cashPrev = Account::create([
        'coa_version_id' => $this->coaPrev->id,
        'code' => '11100',
        'name' => 'Cash',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'level' => 2,
        'is_active' => true,
        'is_cash_flow' => true,
    ]);

    $assetHeader = Account::create([
        'coa_version_id' => $this->coaCurr->id,
        'code' => '10000',
        'name' => 'Assets',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'level' => 1,
        'is_active' => true,
        'is_cash_flow' => false,
    ]);

    $cashInBank = Account::create([
        'coa_version_id' => $this->coaCurr->id,
        'code' => '11110',
        'name' => 'Cash in Bank',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'level' => 2,
        'parent_id' => $assetHeader->id,
        'is_active' => true,
        'is_cash_flow' => true,
    ]);

    $pettyCash = Account::create([
        'coa_version_id' => $this->coaCurr->id,
        'code' => '11120',
        'name' => 'Petty Cash',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'level' => 2,
        'parent_id' => $assetHeader->id,
        'is_active' => true,
        'is_cash_flow' => true,
    ]);

    AccountMapping::create([
        'source_account_id' => $cashPrev->id,
        'target_account_id' => $cashInBank->id,
        'type' => 'split',
        'notes' => 'primary',
    ]);

    AccountMapping::create([
        'source_account_id' => $cashPrev->id,
        'target_account_id' => $pettyCash->id,
        'type' => 'split',
        'notes' => 'secondary',
    ]);

    $jePrev = JournalEntry::create([
        'fiscal_year_id' => $this->fyPrev->id,
        'entry_number' => 'JV-PREV-1',
        'entry_date' => '2025-02-01',
        'description' => 'Prev cash',
        'status' => 'posted',
        'created_by' => $this->user->id,
    ]);

    JournalEntryLine::create([
        'journal_entry_id' => $jePrev->id,
        'account_id' => $cashPrev->id,
        'debit' => 300,
        'credit' => 0,
    ]);

    actingAs($this->user)
        ->get(route('reports.comparative', ['fiscal_year_id' => $this->fyCurr->id, 'comparison_year_id' => $this->fyPrev->id]))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('reports/comparative/index')
            ->where('report.totals.assets', 0)
            ->where('report.totals.comparison_assets', 300)
            ->where('report.totals.change_assets', -300)
        );
});
