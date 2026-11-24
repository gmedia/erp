<?php

use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class)->group('trial-balance-report');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['trial_balance_report']);
    Sanctum::actingAs($this->user, ['*']);
});

test('trial balance places a credit-normal account balance in the credit column', function () {
    $fiscalYear = FiscalYear::factory()->create(['status' => 'open']);
    $liability = Account::factory()->create(['code' => '2010', 'name' => 'Utang', 'type' => 'liability', 'normal_balance' => 'credit']);

    // closing_balance disimpan dalam konvensi normal balance: saldo kredit 500 = +500.
    AccountBalance::factory()->create([
        'account_id' => $liability->id,
        'fiscal_year_id' => $fiscalYear->id,
        'period_month' => 1,
        'period_year' => 2026,
        'opening_balance' => 0,
        'debit_total' => 0,
        'credit_total' => 500,
        'movement' => 500,
        'closing_balance' => 500,
    ]);

    $row = collect(getJson("/api/reports/trial-balance-detailed?fiscal_year_id={$fiscalYear->id}&period_month=1&period_year=2026")
        ->assertOk()
        ->json('data'))->firstWhere('account_code', '2010');

    expect((float) $row['credit_balance'])->toBe(500.0);
    expect((float) $row['debit_balance'])->toBe(0.0);
});

test('trial balance keeps a debit-normal account balance in the debit column', function () {
    $fiscalYear = FiscalYear::factory()->create(['status' => 'open']);
    $cash = Account::factory()->create(['code' => '1010', 'name' => 'Kas', 'type' => 'asset', 'normal_balance' => 'debit']);

    AccountBalance::factory()->create([
        'account_id' => $cash->id,
        'fiscal_year_id' => $fiscalYear->id,
        'period_month' => 1,
        'period_year' => 2026,
        'opening_balance' => 0,
        'debit_total' => 800,
        'credit_total' => 0,
        'movement' => 800,
        'closing_balance' => 800,
    ]);

    $row = collect(getJson("/api/reports/trial-balance-detailed?fiscal_year_id={$fiscalYear->id}&period_month=1&period_year=2026")
        ->assertOk()
        ->json('data'))->firstWhere('account_code', '1010');

    expect((float) $row['debit_balance'])->toBe(800.0);
    expect((float) $row['credit_balance'])->toBe(0.0);
});
