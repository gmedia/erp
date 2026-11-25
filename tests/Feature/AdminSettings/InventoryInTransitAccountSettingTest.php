<?php

use App\Models\Account;
use App\Models\Setting;
use Database\Seeders\CoaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('admin-settings');

beforeEach(function () {
    Sanctum::actingAs(createTestUserWithPermissions(['admin_setting', 'admin_setting.edit']));
});

test('admin settings menyimpan dan membaca akun persediaan dalam perjalanan dengan labelnya', function () {
    $account = Account::factory()->inActiveCoa()->create(['name' => 'Persediaan dalam Perjalanan']);

    putJson('/api/admin-settings', [
        'inventory_in_transit_account_id' => $account->id,
    ])->assertOk();

    expect(Setting::get('inventory_in_transit_account_id'))->toBe((string) $account->id);

    $this->getJson('/api/admin-settings')
        ->assertOk()
        ->assertJsonPath('settings.accounting.inventory_in_transit_account_id', (string) $account->id)
        ->assertJsonPath('settings.accounting.inventory_in_transit_account_label', 'Persediaan dalam Perjalanan');
});

test('mengosongkan akun persediaan dalam perjalanan membuat id dan label null', function () {
    $account = Account::factory()->inActiveCoa()->create();

    putJson('/api/admin-settings', ['inventory_in_transit_account_id' => $account->id])->assertOk();
    putJson('/api/admin-settings', ['inventory_in_transit_account_id' => null])->assertOk();

    $this->getJson('/api/admin-settings')
        ->assertOk()
        ->assertJsonPath('settings.accounting.inventory_in_transit_account_id', null)
        ->assertJsonPath('settings.accounting.inventory_in_transit_account_label', null);
});

test('akun persediaan dalam perjalanan yang tidak ada ditolak 422', function () {
    putJson('/api/admin-settings', ['inventory_in_transit_account_id' => 999999])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['inventory_in_transit_account_id']);
});

test('akun induk (bukan daun) untuk persediaan dalam perjalanan ditolak 422', function () {
    $parent = Account::factory()->inActiveCoa()->create();
    Account::factory()->create([
        'coa_version_id' => $parent->coa_version_id,
        'parent_id' => $parent->id,
    ]);

    putJson('/api/admin-settings', ['inventory_in_transit_account_id' => $parent->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['inventory_in_transit_account_id']);
});

test('seed COA menambahkan akun persediaan dalam perjalanan di bawah aset lancar dan mengisi setting', function () {
    $this->seed(CoaSeeder::class);

    $account = Account::query()->where('code', '11310')->first();

    expect($account)->not->toBeNull()
        ->and($account->name)->toBe('Persediaan dalam Perjalanan')
        ->and($account->type)->toBe('asset')
        ->and($account->normal_balance)->toBe('debit')
        ->and($account->parent?->code)->toBe('11000')
        ->and(Setting::get('inventory_in_transit_account_id'))->toBe((string) $account->id);
});

test('seed COA idempoten untuk akun persediaan dalam perjalanan', function () {
    $this->seed(CoaSeeder::class);
    $this->seed(CoaSeeder::class);

    expect(Account::query()->where('code', '11310')->count())->toBe(1);
});
