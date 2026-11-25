<?php

use App\Models\Account;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetMovement;
use App\Models\Branch;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('asset-disposal-journal');

beforeEach(function () {
    Sanctum::actingAs(createTestUserWithPermissions([
        'asset_movement',
        'asset_movement.create',
        'asset_movement.delete',
    ]));
});

/**
 * COA aktif 2026 dengan akun pelepasan dan satu akun kelompok (punya anak).
 *
 * @return array<string, Account>
 */
function makeSaleAccounts(): array
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
        'cash' => $make('11100', 'Kas', 'asset', 'debit'),
    ];

    Setting::set('asset_disposal_gain_account_id', (string) $accounts['gain']->id);
    Setting::set('asset_disposal_loss_account_id', (string) $accounts['loss']->id);

    return $accounts;
}

function makeSaleAsset(array $accounts, string $code, float $accumulated = 2400000): Asset
{
    return Asset::factory()->create([
        'asset_category_id' => AssetCategory::factory()->create()->id,
        'branch_id' => Branch::factory()->create()->id,
        'asset_code' => $code,
        'name' => 'Mesin Fotokopi',
        'status' => 'active',
        'purchase_date' => '2026-01-05',
        'purchase_cost' => 12000000,
        'salvage_value' => 0,
        'depreciation_method' => 'straight_line',
        'depreciation_start_date' => '2026-01-05',
        'useful_life_months' => 60,
        'accumulated_depreciation' => $accumulated,
        'book_value' => 12000000 - $accumulated,
        'depreciation_expense_account_id' => $accounts['loss']->id,
        'accumulated_depr_account_id' => $accounts['accumulated']->id,
    ]);
}

function disposePayload(Asset $asset, array $extra = []): array
{
    return [
        'asset_id' => $asset->id,
        'movement_type' => 'dispose',
        'moved_at' => '2026-06-30 10:00:00',
        ...$extra,
    ];
}

it('books a gain when sale proceeds exceed the book value', function () {
    $accounts = makeSaleAccounts();
    $asset = makeSaleAsset($accounts, 'FA-000101');

    // Nilai buku 9.600.000, hasil jual 10.000.000 -> laba 400.000.
    postJson('/api/asset-movements', disposePayload($asset, [
        'sale_amount' => 10000000,
        'sale_account_id' => $accounts['cash']->id,
    ]))->assertStatus(201);

    $lines = JournalEntry::where('reference', 'DISP-FA-000101')->firstOrFail()->lines->keyBy('account_id');

    expect((float) $lines[$accounts['cash']->id]->debit)->toBe(10000000.0)
        ->and((float) $lines[$accounts['gain']->id]->credit)->toBe(400000.0)
        ->and($lines->has($accounts['loss']->id))->toBeFalse();
});

it('books a loss when sale proceeds are below the book value', function () {
    $accounts = makeSaleAccounts();
    $asset = makeSaleAsset($accounts, 'FA-000102');

    // Nilai buku 9.600.000, hasil jual 8.000.000 -> rugi 1.600.000.
    postJson('/api/asset-movements', disposePayload($asset, [
        'sale_amount' => 8000000,
        'sale_account_id' => $accounts['cash']->id,
    ]))->assertStatus(201);

    $lines = JournalEntry::where('reference', 'DISP-FA-000102')->firstOrFail()->lines->keyBy('account_id');

    expect((float) $lines[$accounts['loss']->id]->debit)->toBe(1600000.0)
        ->and($lines->has($accounts['gain']->id))->toBeFalse();
});

it('books neither gain nor loss when proceeds equal the book value', function () {
    $accounts = makeSaleAccounts();
    $asset = makeSaleAsset($accounts, 'FA-000103');

    postJson('/api/asset-movements', disposePayload($asset, [
        'sale_amount' => 9600000,
        'sale_account_id' => $accounts['cash']->id,
    ]))->assertStatus(201);

    $lines = JournalEntry::where('reference', 'DISP-FA-000103')->firstOrFail()->lines;

    expect($lines->where('account_id', $accounts['gain']->id))->toHaveCount(0)
        ->and($lines->where('account_id', $accounts['loss']->id))->toHaveCount(0)
        ->and((float) $lines->sum('debit'))->toBe((float) $lines->sum('credit'));
});

it('rejects a sale with a zero or missing amount', function () {
    $accounts = makeSaleAccounts();
    $asset = makeSaleAsset($accounts, 'FA-000104');

    postJson('/api/asset-movements', disposePayload($asset, [
        'sale_amount' => 0,
        'sale_account_id' => $accounts['cash']->id,
    ]))->assertStatus(422);

    postJson('/api/asset-movements', disposePayload($asset, [
        'sale_account_id' => $accounts['cash']->id,
    ]))->assertStatus(422);

    expect(JournalEntry::where('reference', 'DISP-FA-000104')->exists())->toBeFalse();
});

it('rejects a sale without a receipt account', function () {
    $accounts = makeSaleAccounts();
    $asset = makeSaleAsset($accounts, 'FA-000105');

    postJson('/api/asset-movements', disposePayload($asset, [
        'sale_amount' => 10000000,
    ]))->assertStatus(422);

    expect(JournalEntry::where('reference', 'DISP-FA-000105')->exists())->toBeFalse();
});

it('rejects a receipt account that is a group account', function () {
    $accounts = makeSaleAccounts();
    $asset = makeSaleAsset($accounts, 'FA-000106');

    Account::factory()->create([
        'coa_version_id' => $accounts['cash']->coa_version_id,
        'code' => '11000',
        'name' => 'Aset Lancar',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'is_active' => true,
        'parent_id' => null,
    ]);
    $group = Account::where('code', '11000')->firstOrFail();
    Account::factory()->create([
        'coa_version_id' => $group->coa_version_id,
        'code' => '11101',
        'name' => 'Kas Kecil',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'is_active' => true,
        'parent_id' => $group->id,
    ]);

    postJson('/api/asset-movements', disposePayload($asset, [
        'sale_amount' => 10000000,
        'sale_account_id' => $group->id,
    ]))->assertStatus(422);

    expect(JournalEntry::where('reference', 'DISP-FA-000106')->exists())->toBeFalse();
});

it('rejects a sale when the receipt account is not in the active COA', function () {
    $accounts = makeSaleAccounts();
    $asset = makeSaleAsset($accounts, 'FA-000107');

    $otherVersion = CoaVersion::factory()->create(['status' => 'archived']);
    $foreign = Account::factory()->create([
        'coa_version_id' => $otherVersion->id,
        'code' => '11100',
        'name' => 'Kas Lama',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);

    postJson('/api/asset-movements', disposePayload($asset, [
        'sale_amount' => 10000000,
        'sale_account_id' => $foreign->id,
    ]))->assertStatus(422);

    expect(JournalEntry::where('reference', 'DISP-FA-000107')->exists())->toBeFalse();
});

it('reverses the disposal journal and restores the asset when the disposal is deleted', function () {
    $accounts = makeSaleAccounts();
    $asset = makeSaleAsset($accounts, 'FA-000108');

    $movement = postJson('/api/asset-movements', disposePayload($asset, [
        'sale_amount' => 10000000,
        'sale_account_id' => $accounts['cash']->id,
    ]))->assertStatus(201)->json('data.id');

    deleteJson("/api/asset-movements/{$movement}")->assertOk();

    $original = JournalEntry::where('reference', 'DISP-FA-000108')->firstOrFail();
    $reversal = JournalEntry::where('reference', 'REV-DISP-FA-000108')->firstOrFail();

    expect($reversal->lines)->toHaveCount($original->lines->count());

    $originalDebit = (float) $original->lines->sum('debit');
    $reversalDebit = (float) $reversal->lines->sum('debit');
    expect($reversalDebit)->toBe((float) $original->lines->sum('credit'))
        ->and($originalDebit)->toBe((float) $reversal->lines->sum('credit'));

    assertDatabaseHas('assets', [
        'id' => $asset->id,
        'status' => 'active',
        'accumulated_depreciation' => 2400000,
        'book_value' => 9600000,
    ]);
    expect(AssetMovement::where('asset_id', $asset->id)->count())->toBe(0);
});

it('does not post a second disposal journal for the same asset', function () {
    $accounts = makeSaleAccounts();
    $asset = makeSaleAsset($accounts, 'FA-000109');

    postJson('/api/asset-movements', disposePayload($asset, [
        'sale_amount' => 10000000,
        'sale_account_id' => $accounts['cash']->id,
    ]))->assertStatus(201);

    postJson('/api/asset-movements', disposePayload($asset, [
        'sale_amount' => 10000000,
        'sale_account_id' => $accounts['cash']->id,
    ]))->assertStatus(422);

    expect(JournalEntry::where('reference', 'DISP-FA-000109')->count())->toBe(1);
});

it('rejects system fields in the disposal payload without changing the asset', function () {
    $accounts = makeSaleAccounts();
    $asset = makeSaleAsset($accounts, 'FA-000110');

    postJson('/api/asset-movements', disposePayload($asset, [
        'status' => 'lost',
        'status_before_disposal' => 'lost',
        'accumulated_depreciation' => 0,
        'book_value' => 99999999,
    ]))->assertStatus(422);

    assertDatabaseHas('assets', [
        'id' => $asset->id,
        'status' => 'active',
        'accumulated_depreciation' => 2400000,
        'book_value' => 9600000,
    ]);
    expect(AssetMovement::where('asset_id', $asset->id)->count())->toBe(0)
        ->and(JournalEntry::where('reference', 'DISP-FA-000110')->exists())->toBeFalse();
});

it('rejects a disposal dated in a closed fiscal period and keeps the asset active', function () {
    $accounts = makeSaleAccounts();
    FiscalYear::where('name', '2026')->update(['status' => 'closed']);
    $asset = makeSaleAsset($accounts, 'FA-000111');

    postJson('/api/asset-movements', disposePayload($asset, [
        'sale_amount' => 10000000,
        'sale_account_id' => $accounts['cash']->id,
    ]))->assertStatus(422);

    assertDatabaseHas('assets', ['id' => $asset->id, 'status' => 'active']);
});
