<?php

use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class)->group('reports', 'reports-balance-sheet');

beforeEach(function () {
    seed();

    $this->fiscalYear = FiscalYear::where('status', 'open')->firstOrFail();
    $this->user = createTestUserWithPermissions(['balance_sheet_report']);
});

test('balance sheet memasukkan current year earnings (net income) ke equity', function () {
    actingAs($this->user)
        ->get(route('reports.balance-sheet', ['fiscal_year_id' => $this->fiscalYear->id]))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('reports/balance-sheet/index')
            ->where('selectedYearId', $this->fiscalYear->id)
            ->has('fiscalYears')
            ->where('report.totals.assets', fn ($value) => (float) $value === 8000000.0)
            ->where('report.totals.liabilities', fn ($value) => (float) $value === 3000000.0)
            ->where('report.totals.equity', fn ($value) => (float) $value === 5000000.0)
            ->where('report.equity.1.code', '9999-CYE')
            ->where('report.equity.1.balance', fn ($value) => (float) $value === 5000000.0)
        );
});

