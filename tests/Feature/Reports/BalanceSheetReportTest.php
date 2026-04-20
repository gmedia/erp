<?php

namespace Tests\Feature\Reports;

use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class)->group('reports');

beforeEach(function () {
    seed();

    $this->fiscalYear = FiscalYear::where('status', 'open')->firstOrFail();
    $this->user = createTestUserWithPermissions(['balance_sheet_report']);
});

test('balance sheet memasukkan current year earnings (net income) ke equity', function () {
    Sanctum::actingAs($this->user, ['*']);
    $this->getJson('/api/reports/balance-sheet?fiscal_year_id=' . $this->fiscalYear->id)
        ->assertStatus(200)
        ->assertJsonPath('selectedYearId', $this->fiscalYear->id)
        ->assertJsonPath('report.totals.assets', 8000000)
        ->assertJsonPath('report.totals.liabilities', 3000000)
        ->assertJsonPath('report.totals.equity', 5000000)
        ->assertJsonPath('report.equity.1.code', '9999-CYE')
        ->assertJsonPath('report.equity.1.balance', 5000000);
});

test('balance sheet returns an empty structured report when fiscal year has no coa version', function () {
    $fiscalYearWithoutCoa = FiscalYear::create([
        'name' => '2027',
        'start_date' => '2027-01-01',
        'end_date' => '2027-12-31',
        'status' => 'open',
    ]);

    Sanctum::actingAs($this->user, ['*']);
    $this->getJson('/api/reports/balance-sheet?fiscal_year_id=' . $fiscalYearWithoutCoa->id)
        ->assertOk()
        ->assertJsonPath('selectedYearId', $fiscalYearWithoutCoa->id)
        ->assertJsonPath('report.assets', [])
        ->assertJsonPath('report.liabilities', [])
        ->assertJsonPath('report.equity', [])
        ->assertJsonPath('report.totals.assets', 0)
        ->assertJsonPath('report.totals.liabilities', 0)
        ->assertJsonPath('report.totals.equity', 0);
});
