<?php

namespace Tests\Feature\Reports;

use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class)->group('reports');

beforeEach(function () {
    seed();

    $this->fiscalYear = FiscalYear::where('status', 'open')->firstOrFail();
    $this->user = createTestUserWithPermissions(['income_statement_report']);
});

test('income statement menampilkan laporan sesuai seed (posted saja)', function () {
    \Laravel\Sanctum\Sanctum::actingAs($this->user, ['*']);
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

