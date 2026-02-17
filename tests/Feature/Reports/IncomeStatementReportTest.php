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
    actingAs($this->user)
        ->get(route('reports.income-statement', ['fiscal_year_id' => $this->fiscalYear->id]))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('reports/income-statement/index')
            ->where('selectedYearId', $this->fiscalYear->id)
            ->has('fiscalYears')
            ->where('report.totals.revenue', fn ($value) => (float) $value === 5000000.0)
            ->where('report.totals.expense', fn ($value) => (float) $value === 0.0)
            ->where('report.totals.net_income', fn ($value) => (float) $value === 5000000.0)
            ->where('report.revenues.0.code', '40000')
            ->where('report.revenues.0.children.0.code', '41000')
            ->where('report.revenues.0.children.0.balance', fn ($value) => (float) $value === 5000000.0)
        );
});

