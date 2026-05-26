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

test('it can export balance sheet report', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-04 10:00:00'));
    Excel::fake();
    Storage::fake('public');

    Sanctum::actingAs($this->user, ['*']);
    $response = $this->postJson('/api/reports/balance-sheet/export', [
        'fiscal_year_id' => $this->fiscalYear->id,
    ])
        ->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');
    expect($filename)->toStartWith('balance_sheet_report_');
    expect($filename)->toEndWith('.xlsx');

    Excel::assertStored('exports/' . $filename, 'public');
    Carbon::setTestNow();
});

test('it can export balance sheet report with comparison year', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-04 10:00:00'));
    Excel::fake();
    Storage::fake('public');

    $comparisonYear = FiscalYear::create([
        'name' => '2024',
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
        'status' => 'closed',
    ]);

    Sanctum::actingAs($this->user, ['*']);
    $response = $this->postJson('/api/reports/balance-sheet/export', [
        'fiscal_year_id' => $this->fiscalYear->id,
        'comparison_year_id' => $comparisonYear->id,
    ])
        ->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');
    expect($filename)->toStartWith('balance_sheet_report_');
    expect($filename)->toEndWith('.xlsx');

    Excel::assertStored('exports/' . $filename, 'public');
    Carbon::setTestNow();
});

test('it can export balance sheet report for fiscal year without coa version (regression: empty totals shape)', function () {
    $fiscalYearWithoutCoa = FiscalYear::create([
        'name' => '2027-empty',
        'start_date' => '2027-01-01',
        'end_date' => '2027-12-31',
        'status' => 'open',
    ]);

    Carbon::setTestNow(Carbon::parse('2026-03-04 10:00:00'));
    Excel::fake();
    Storage::fake('public');

    Sanctum::actingAs($this->user, ['*']);
    $response = $this->postJson('/api/reports/balance-sheet/export', [
        'fiscal_year_id' => $fiscalYearWithoutCoa->id,
    ])
        ->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');
    expect($filename)->toStartWith('balance_sheet_report_');
    expect($filename)->toEndWith('.xlsx');

    Excel::assertStored('exports/' . $filename, 'public');
    Carbon::setTestNow();
});

test('it requires permission to export balance sheet report', function () {
    $userWithoutPermission = createTestUserWithPermissions([]);

    Sanctum::actingAs($userWithoutPermission, ['*']);
    $this->postJson('/api/reports/balance-sheet/export', [
        'fiscal_year_id' => $this->fiscalYear->id,
    ])->assertForbidden();
});

test('it validates fiscal_year_id when exporting balance sheet', function () {
    Sanctum::actingAs($this->user, ['*']);

    $this->postJson('/api/reports/balance-sheet/export', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['fiscal_year_id']);

    $this->postJson('/api/reports/balance-sheet/export', [
        'fiscal_year_id' => 99999,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['fiscal_year_id']);
});
