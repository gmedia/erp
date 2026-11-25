<?php

use App\Models\Account;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('admin-settings');

/**
 * Menjalankan migration backfill (issue #495) pada keadaan database test saat ini.
 */
function runInTransitBackfillMigration(): void
{
    $migration = require database_path('migrations/2026_11_25_100100_backfill_inventory_in_transit_account_setting.php');
    $migration->up();
}

test('backfill mengisi setting bila akun 11310 aktif ada di COA aktif', function () {
    $account = Account::factory()->inActiveCoa()->create(['code' => '11310']);
    Setting::query()->where('key', 'inventory_in_transit_account_id')->update(['value' => null]);

    runInTransitBackfillMigration();

    expect(Setting::get('inventory_in_transit_account_id'))->toBe((string) $account->id);
});

test('backfill membiarkan setting null bila akun 11310 tidak ada', function () {
    Setting::query()->where('key', 'inventory_in_transit_account_id')->update(['value' => null]);

    runInTransitBackfillMigration();

    expect(Setting::get('inventory_in_transit_account_id'))->toBeNull();
});

test('backfill tidak menimpa setting yang sudah terisi dan idempoten', function () {
    Account::factory()->inActiveCoa()->create(['code' => '11310']);
    $other = Account::factory()->inActiveCoa()->create(['code' => '11311']);
    Setting::set('inventory_in_transit_account_id', (string) $other->id);

    runInTransitBackfillMigration();
    runInTransitBackfillMigration();

    expect(Setting::get('inventory_in_transit_account_id'))->toBe((string) $other->id);
});

test('backfill mengabaikan akun 11310 yang tidak aktif', function () {
    Account::factory()->inActiveCoa()->create(['code' => '11310', 'is_active' => false]);
    Setting::query()->where('key', 'inventory_in_transit_account_id')->update(['value' => null]);

    runInTransitBackfillMigration();

    expect(Setting::get('inventory_in_transit_account_id'))->toBeNull();
});
