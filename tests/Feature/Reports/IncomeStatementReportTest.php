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
    $this->user = createTestUserWithPermissions(['income_statement_report']);
});

test('income statement menampilkan laporan sesuai seed (posted saja)', function () {
    Sanctum::actingAs($this->user, ['*']);
    $this->getJson('/api/reports/income-statement?fiscal_year_id=' . $this->fiscalYear->id)
        ->assertStatus(200)
        ->assertJsonPath('selectedYearId', $this->fiscalYear->id)
        ->assertJsonPath('report.totals.revenue', 5000000)
        ->assertJsonPath('report.totals.expense', 0)
        ->assertJsonPath('report.totals.net_income', 5000000)
        ->assertJsonPath('report.revenues.0.code', '40000')
        ->assertJsonPath('report.revenues.0.children.0.code', '41000')
        ->assertJsonPath('report.revenues.0.children.0.balance', 5000000);
});

test('income statement returns an empty structured report when fiscal year has no coa version', function () {
    $fiscalYearWithoutCoa = FiscalYear::create([
        'name' => '2027',
        'start_date' => '2027-01-01',
        'end_date' => '2027-12-31',
        'status' => 'open',
    ]);

    Sanctum::actingAs($this->user, ['*']);
    $this->getJson('/api/reports/income-statement?fiscal_year_id=' . $fiscalYearWithoutCoa->id)
        ->assertOk()
        ->assertJsonPath('selectedYearId', $fiscalYearWithoutCoa->id)
        ->assertJsonPath('report.revenues', [])
        ->assertJsonPath('report.expenses', [])
        ->assertJsonPath('report.totals.revenue', 0)
        ->assertJsonPath('report.totals.expense', 0)
        ->assertJsonPath('report.totals.net_income', 0);
});
