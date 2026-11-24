<?php

namespace Tests\Feature\AssetMovements;

use App\Models\Account;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Branch;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('asset-disposal-journal');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'asset_movement',
        'asset_movement.create',
    ]);
    Sanctum::actingAs($user);
});

/**
 * @return array{equipment: Account, accumulated: Account, loss: Account}
 */
function makeControllerDisposalAccounts(): array
{
    $fiscalYear = FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);
    $coaVersion = CoaVersion::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'active',
    ]);

    $make = fn (string $code, string $name, string $type, string $normal): Account => Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => $code,
        'name' => $name,
        'type' => $type,
        'normal_balance' => $normal,
        'is_active' => true,
    ]);

    $accounts = [
        'equipment' => $make('12100', 'Equipment', 'asset', 'debit'),
        'accumulated' => $make('12200', 'Accumulated Depreciation - Equipment', 'asset', 'credit'),
        'gain' => $make('42000', 'Gain on Disposal of Assets', 'revenue', 'credit'),
        'loss' => $make('52000', 'Loss on Disposal of Assets', 'expense', 'debit'),
    ];

    Setting::set('asset_disposal_gain_account_id', (string) $accounts['gain']->id);
    Setting::set('asset_disposal_loss_account_id', (string) $accounts['loss']->id);

    return $accounts;
}

test('dispose movement posts the disposal journal on the movement date', function () {
    $accounts = makeControllerDisposalAccounts();
    $asset = Asset::factory()->create([
        'asset_category_id' => AssetCategory::factory()->create()->id,
        'branch_id' => Branch::factory()->create()->id,
        'asset_code' => 'FA-000031',
        'status' => 'active',
        'purchase_date' => '2026-01-05',
        'purchase_cost' => 12000000,
        'salvage_value' => 0,
        'depreciation_method' => 'straight_line',
        'depreciation_start_date' => '2026-01-05',
        'useful_life_months' => 60,
        'accumulated_depreciation' => 2400000,
        'book_value' => 9600000,
        'depreciation_expense_account_id' => $accounts['loss']->id,
        'accumulated_depr_account_id' => $accounts['accumulated']->id,
    ]);

    postJson('/api/asset-movements', [
        'asset_id' => $asset->id,
        'movement_type' => 'dispose',
        'moved_at' => '2026-06-30 10:00:00',
    ])->assertStatus(201);

    $entry = JournalEntry::where('reference', 'DISP-FA-000031')->firstOrFail();
    expect($entry->entry_date->toDateString())->toBe('2026-06-30');
    assertDatabaseHas('assets', ['id' => $asset->id, 'status' => 'disposed']);
});

test('posting a depreciation run calculated before a disposal is rejected for the disposed asset', function () {
    makeControllerDisposalAccounts();
    $fiscalYear = FiscalYear::where('name', '2026')->firstOrFail();
    $asset = Asset::factory()->create([
        'asset_category_id' => AssetCategory::factory()->create()->id,
        'branch_id' => Branch::factory()->create()->id,
        'asset_code' => 'FA-000033',
        'status' => 'active',
        'purchase_date' => '2026-01-05',
        'purchase_cost' => 12000000,
        'salvage_value' => 0,
        'depreciation_method' => 'straight_line',
        'depreciation_start_date' => '2026-01-05',
        'useful_life_months' => 60,
        'accumulated_depreciation' => 0,
        'book_value' => 12000000,
        'depreciation_expense_account_id' => Account::where('code', '52000')->value('id'),
        'accumulated_depr_account_id' => Account::where('code', '12200')->value('id'),
    ]);

    Sanctum::actingAs(createTestUserWithPermissions([
        'asset_depreciation_run',
        'asset_depreciation_run.edit',
        'asset_movement',
        'asset_movement.create',
    ]));

    $run = postJson('/api/asset-depreciation-runs/calculate', [
        'fiscal_year_id' => $fiscalYear->id,
        'period_start' => '2026-03-01',
        'period_end' => '2026-03-31',
    ])->assertStatus(200)->json('run');

    postJson('/api/asset-movements', [
        'asset_id' => $asset->id,
        'movement_type' => 'dispose',
        'moved_at' => '2026-03-15 09:00:00',
    ])->assertStatus(201);

    postJson("/api/asset-depreciation-runs/{$run['id']}/post")->assertStatus(422);

    expect(JournalEntry::where('reference', 'DEPR-202603')->exists())->toBeFalse()
        ->and(Asset::find($asset->id)->status)->toBe('disposed');
});

test('dispose movement is rolled back when the fiscal period is closed', function () {
    $accounts = makeControllerDisposalAccounts();
    FiscalYear::where('name', '2026')->update(['status' => 'closed']);
    $asset = Asset::factory()->create([
        'asset_category_id' => AssetCategory::factory()->create()->id,
        'branch_id' => Branch::factory()->create()->id,
        'asset_code' => 'FA-000032',
        'status' => 'active',
        'purchase_date' => '2026-01-05',
        'purchase_cost' => 12000000,
        'salvage_value' => 0,
        'depreciation_method' => 'straight_line',
        'depreciation_start_date' => '2026-01-05',
        'useful_life_months' => 60,
        'accumulated_depreciation' => 2400000,
        'book_value' => 9600000,
        'depreciation_expense_account_id' => $accounts['loss']->id,
        'accumulated_depr_account_id' => $accounts['accumulated']->id,
    ]);

    postJson('/api/asset-movements', [
        'asset_id' => $asset->id,
        'movement_type' => 'dispose',
        'moved_at' => '2026-06-30 10:00:00',
    ])->assertStatus(422);

    assertDatabaseHas('assets', ['id' => $asset->id, 'status' => 'active']);
    expect(JournalEntry::where('reference', 'DISP-FA-000032')->exists())->toBeFalse();
});
