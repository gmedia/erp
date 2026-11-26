<?php

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('locale');

beforeEach(function () {
    $user = createTestUserWithPermissions(['asset', 'asset.create', 'asset.edit', 'asset.delete']);
    Sanctum::actingAs($user, ['*']);
});

function assetApiPayload(): array
{
    return [
        'asset_code' => 'AST-LOC-001',
        'name' => 'Locale Asset',
        'asset_category_id' => AssetCategory::factory()->create()->id,
        'branch_id' => Branch::factory()->create()->id,
        'purchase_date' => '2023-01-01',
        'purchase_cost' => 1000000,
        'currency' => 'IDR',
        'status' => 'active',
        'depreciation_method' => 'straight_line',
    ];
}

test('asset create message defaults to english without locale header', function () {
    postJson('/api/assets', assetApiPayload())
        ->assertStatus(201)
        ->assertJsonPath('message', 'Asset created successfully');
});

test('asset create message is indonesian with X-Locale id', function () {
    postJson('/api/assets', assetApiPayload(), ['X-Locale' => 'id'])
        ->assertStatus(201)
        ->assertJsonPath('message', 'Aset berhasil dibuat');
});

test('asset delete message is indonesian with X-Locale id', function () {
    $asset = Asset::factory()->create();

    deleteJson("/api/assets/{$asset->ulid}", [], ['X-Locale' => 'id'])
        ->assertOk()
        ->assertJsonPath('message', 'Aset berhasil dihapus');
});

test('asset delete message defaults to english without locale header', function () {
    $asset = Asset::factory()->create();

    deleteJson("/api/assets/{$asset->ulid}")
        ->assertOk()
        ->assertJsonPath('message', 'Asset deleted successfully');
});

test('asset api message keys exist in both english and indonesian locales', function () {
    $en = json_decode(File::get(lang_path('en.json')), true, 512, JSON_THROW_ON_ERROR);
    $id = json_decode(File::get(lang_path('id.json')), true, 512, JSON_THROW_ON_ERROR);

    $keys = array_filter(array_keys($en), fn (string $key) => str_starts_with($key, 'asset_api.'));

    expect($keys)->not->toBeEmpty();
    foreach ($keys as $key) {
        expect(array_key_exists($key, $id))->toBeTrue("{$key} missing in id.json");
        expect($id[$key])->not->toBe($en[$key], "{$key} not translated");
    }
});
