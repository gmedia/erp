<?php

use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

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

    getJson("/api/reports/trial-balance?fiscal_year_id={$fiscalYear->id}&period_month=1&period_year=2026")
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

    getJson("/api/reports/trial-balance?fiscal_year_id={$fiscalYear->id}&period_month=2&period_year=2026")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.account_name', 'Filtered Cash');
});

test('it can export trial balance report', function () {
    $fiscalYear = FiscalYear::factory()->create();

    $response = postJson('/api/reports/trial-balance/export', [
        'fiscal_year_id' => $fiscalYear->id,
        'period_month' => 1,
        'period_year' => 2026,
    ])->assertOk()->assertJsonStructure(['url', 'filename']);

    expect($response->json('filename'))->toEndWith('.xlsx');
});
