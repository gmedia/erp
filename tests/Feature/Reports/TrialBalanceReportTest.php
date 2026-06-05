<?php

use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('trial-balance-report');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['trial_balance_report']);
    Sanctum::actingAs($this->user, ['*']);
});

test('it can get trial balance report', function () {
    $fiscalYear = FiscalYear::factory()->create(['status' => 'open']);
    $account = Account::factory()->create(['code' => '1010', 'name' => 'Cash', 'type' => 'asset']);
    AccountBalance::factory()->create(['account_id' => $account->id, 'fiscal_year_id' => $fiscalYear->id, 'period_month' => 1, 'period_year' => 2026, 'closing_balance' => 1000]);

    getJson("/api/reports/trial-balance-detailed?fiscal_year_id={$fiscalYear->id}&period_month=1&period_year=2026")
        ->assertOk()
        ->assertJsonPath('data.0.account_name', 'Cash')
        ->assertJsonPath('summary.total_debit', 1000);
});

test('it filters by fiscal year and period', function () {
    $fiscalYear = FiscalYear::factory()->create();
    $otherFiscalYear = FiscalYear::factory()->create();
    $account = Account::factory()->create(['name' => 'Filtered Cash']);
    AccountBalance::factory()->create(['account_id' => $account->id, 'fiscal_year_id' => $fiscalYear->id, 'period_month' => 2, 'period_year' => 2026, 'closing_balance' => 1500]);
    AccountBalance::factory()->create(['fiscal_year_id' => $otherFiscalYear->id, 'period_month' => 1, 'period_year' => 2026, 'closing_balance' => 2500]);

    getJson("/api/reports/trial-balance-detailed?fiscal_year_id={$fiscalYear->id}&period_month=2&period_year=2026")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.account_name', 'Filtered Cash');
});

test('it can export trial balance report', function () {
    $fiscalYear = FiscalYear::factory()->create();

    $response = postJson('/api/reports/trial-balance-detailed/export', [
        'fiscal_year_id' => $fiscalYear->id,
        'period_month' => 1,
        'period_year' => 2026,
    ])->assertOk()->assertJsonStructure(['url', 'filename']);

    expect($response->json('filename'))->toEndWith('.xlsx');
});

test('it can export trial balance financial report', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-04 10:00:00'));
    Excel::fake();
    Storage::fake('public');

    $fiscalYear = FiscalYear::factory()->create(['status' => 'open']);

    $response = postJson('/api/reports/trial-balance/export', [
        'fiscal_year_id' => $fiscalYear->id,
    ])->assertOk()->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');
    expect($filename)->toStartWith('trial_balance_financial_report_');
    expect($filename)->toEndWith('.xlsx');

    Excel::assertStored('exports/' . $filename, 'public');
    Carbon::setTestNow();
});

test('it requires permission to export trial balance financial report', function () {
    $userWithoutPermission = createTestUserWithPermissions([]);
    Sanctum::actingAs($userWithoutPermission, ['*']);

    $fiscalYear = FiscalYear::factory()->create(['status' => 'open']);

    postJson('/api/reports/trial-balance/export', [
        'fiscal_year_id' => $fiscalYear->id,
    ])->assertStatus(403);
});

test('it validates fiscal_year_id when exporting trial balance financial report', function () {
    postJson('/api/reports/trial-balance/export', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['fiscal_year_id']);

    postJson('/api/reports/trial-balance/export', [
        'fiscal_year_id' => 99999,
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['fiscal_year_id']);
});

test('it returns 403 without trial_balance_report permission', function () {
    $user = createTestUserWithPermissions([]);
    Sanctum::actingAs($user, ['*']);

    getJson('/api/reports/trial-balance-detailed')->assertForbidden();
});
