<?php

namespace Tests\Feature\Reports;

use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;

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

test('it can export income statement report', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-04 10:00:00'));
    Excel::fake();
    Storage::fake('public');

    Sanctum::actingAs($this->user, ['*']);
    $response = $this->postJson('/api/reports/income-statement/export', [
        'fiscal_year_id' => $this->fiscalYear->id,
    ])
        ->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');
    expect($filename)->toStartWith('income_statement_report_');
    expect($filename)->toEndWith('.xlsx');

    Excel::assertStored('exports/' . $filename, 'public');
    Carbon::setTestNow();
});

test('it can export income statement report with comparison year', function () {
    $comparisonYear = FiscalYear::create([
        'name' => '2028',
        'start_date' => '2028-01-01',
        'end_date' => '2028-12-31',
        'status' => 'open',
    ]);

    Carbon::setTestNow(Carbon::parse('2026-03-04 10:00:00'));
    Excel::fake();
    Storage::fake('public');

    Sanctum::actingAs($this->user, ['*']);
    $response = $this->postJson('/api/reports/income-statement/export', [
        'fiscal_year_id' => $this->fiscalYear->id,
        'comparison_year_id' => $comparisonYear->id,
    ])
        ->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');
    expect($filename)->toStartWith('income_statement_report_');
    expect($filename)->toEndWith('.xlsx');

    Excel::assertStored('exports/' . $filename, 'public');
    Carbon::setTestNow();
});

test('it requires permission to export income statement', function () {
    $userWithoutPermission = createTestUserWithPermissions([]);

    Sanctum::actingAs($userWithoutPermission, ['*']);
    $this->postJson('/api/reports/income-statement/export', [
        'fiscal_year_id' => $this->fiscalYear->id,
    ])->assertForbidden();
});

test('it validates fiscal_year_id when exporting income statement', function () {
    Sanctum::actingAs($this->user, ['*']);

    $this->postJson('/api/reports/income-statement/export', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['fiscal_year_id']);

    $this->postJson('/api/reports/income-statement/export', [
        'fiscal_year_id' => 99999,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['fiscal_year_id']);
});
