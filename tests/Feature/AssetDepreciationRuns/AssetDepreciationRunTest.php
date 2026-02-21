<?php

use App\Models\Asset;
use App\Models\AssetDepreciationRun;
use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\get;

uses(RefreshDatabase::class)->group('asset-depreciation-runs');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['asset_depreciation_run']);
    actingAs($this->user);

    $this->fiscalYear = FiscalYear::factory()->create([
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
        'status' => 'open'
    ]);
});

test('user can view depreciation runs page', function () {
    $response = get('/asset-depreciation-runs');
    $response->assertStatus(200);
});

test('user can calculate depreciation', function () {
    Asset::factory()->create([
        'purchase_cost' => 12000000,
        'salvage_value' => 0,
        'useful_life_months' => 60,
        'depreciation_start_date' => '2024-01-01',
        'depreciation_method' => 'straight_line',
        'status' => 'active',
        'book_value' => 12000000,
        'accumulated_depreciation' => 0,
    ]);

    $response = postJson('/api/asset-depreciation-runs/calculate', [
        'fiscal_year_id' => $this->fiscalYear->id,
        'period_start' => '2024-01-01',
        'period_end' => '2024-01-31',
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('message', 'Depreciation calculated successfully.');

    $this->assertDatabaseHas('asset_depreciation_runs', [
        'fiscal_year_id' => $this->fiscalYear->id,
        'period_start' => '2024-01-01',
        'status' => 'calculated',
    ]);

    $run = AssetDepreciationRun::first();
    $this->assertDatabaseHas('asset_depreciation_lines', [
        'asset_depreciation_run_id' => $run->id,
        'amount' => 200000,
    ]);
});

test('user can view depreciation run lines', function () {
    $run = AssetDepreciationRun::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'status' => 'calculated'
    ]);

    $response = getJson("/api/asset-depreciation-runs/{$run->id}/lines");
    $response->assertStatus(200);
});

test('user can post depreciation run to journal', function () {
    $account = \App\Models\Account::factory()->create();
    
    $asset = Asset::factory()->create([
        'purchase_cost' => 12000000,
        'salvage_value' => 0,
        'useful_life_months' => 60,
        'depreciation_start_date' => '2024-01-01',
        'depreciation_method' => 'straight_line',
        'status' => 'active',
        'book_value' => 12000000,
        'accumulated_depreciation' => 0,
        'depreciation_expense_account_id' => $account->id,
        'accumulated_depr_account_id' => $account->id,
    ]);

    $run = AssetDepreciationRun::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'period_start' => '2024-01-01',
        'period_end' => '2024-01-31',
        'status' => 'calculated'
    ]);

    \App\Models\AssetDepreciationLine::factory()->create([
        'asset_depreciation_run_id' => $run->id,
        'asset_id' => $asset->id,
        'amount' => 200000,
        'accumulated_before' => 0,
        'accumulated_after' => 200000,
        'book_value_after' => 11800000,
    ]);

    $response = postJson("/api/asset-depreciation-runs/{$run->id}/post");
    
    $response->assertStatus(200);
    $response->assertJsonPath('message', 'Depreciation successfully posted to journal.');

    $this->assertDatabaseHas('asset_depreciation_runs', [
        'id' => $run->id,
        'status' => 'posted',
    ]);

    $run->refresh();
    $this->assertNotNull($run->journal_entry_id);

    $this->assertDatabaseHas('journal_entries', [
        'id' => $run->journal_entry_id,
        'status' => 'draft',
    ]);
});
