<?php

use App\Models\Account;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('asset-disposal-journal');

beforeEach(function () {
    Sanctum::actingAs(createTestUserWithPermissions(['admin_setting', 'admin_setting.edit']));
});

test('admin settings menyimpan dan membaca dua akun pelepasan aset dengan labelnya', function () {
    $gain = Account::factory()->create(['name' => 'Laba Pelepasan Aset']);
    $loss = Account::factory()->create(['name' => 'Rugi Pelepasan Aset']);

    putJson('/api/admin-settings', [
        'asset_disposal_gain_account_id' => $gain->id,
        'asset_disposal_loss_account_id' => $loss->id,
    ])->assertOk();

    expect(Setting::get('asset_disposal_gain_account_id'))->toBe((string) $gain->id)
        ->and(Setting::get('asset_disposal_loss_account_id'))->toBe((string) $loss->id);

    $response = $this->getJson('/api/admin-settings')->assertOk();

    $response->assertJsonPath('settings.accounting.asset_disposal_gain_account_id', (string) $gain->id)
        ->assertJsonPath('settings.accounting.asset_disposal_gain_account_label', 'Laba Pelepasan Aset')
        ->assertJsonPath('settings.accounting.asset_disposal_loss_account_id', (string) $loss->id)
        ->assertJsonPath('settings.accounting.asset_disposal_loss_account_label', 'Rugi Pelepasan Aset');
});

test('mengosongkan akun pelepasan membuat label dan id kembali null', function () {
    $gain = Account::factory()->create();

    putJson('/api/admin-settings', ['asset_disposal_gain_account_id' => $gain->id])->assertOk();
    putJson('/api/admin-settings', ['asset_disposal_gain_account_id' => null])->assertOk();

    $this->getJson('/api/admin-settings')
        ->assertOk()
        ->assertJsonPath('settings.accounting.asset_disposal_gain_account_id', null)
        ->assertJsonPath('settings.accounting.asset_disposal_gain_account_label', null);
});

test('akun pelepasan yang tidak ada ditolak 422', function () {
    putJson('/api/admin-settings', ['asset_disposal_loss_account_id' => 999999])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['asset_disposal_loss_account_id']);
});
