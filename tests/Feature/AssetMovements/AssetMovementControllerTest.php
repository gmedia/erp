<?php

namespace Tests\Feature\AssetMovements;

use App\Models\Asset;
use App\Models\AssetLocation;
use App\Models\AssetMovement;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('asset-movements');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'asset_movement',
        'asset_movement.create',
        'asset_movement.edit',
        'asset_movement.delete',
    ]);
    actingAs($user);
});

test('it returns asset movement index', function () {
    AssetMovement::factory()->count(3)->create();

    $response = getJson('/api/asset-movements');

    $response->assertStatus(200)
        ->assertJsonStructure(['data', 'meta', 'links']);
});

test('it stores new asset movement', function () {
    $asset = Asset::factory()->create();
    $toBranch = Branch::factory()->create();
    $toLocation = AssetLocation::factory()->create(['branch_id' => $toBranch->id]);

    $data = [
        'asset_id' => $asset->id,
        'movement_type' => 'transfer',
        'moved_at' => now()->format('Y-m-d H:i:s'),
        'to_branch_id' => $toBranch->id,
        'to_location_id' => $toLocation->id,
        'reference' => 'MOV-001',
        'notes' => 'Test transfer',
    ];

    $response = postJson('/api/asset-movements', $data);

    $response->assertStatus(201);
    assertDatabaseHas('asset_movements', ['reference' => 'MOV-001']);
});

test('it shows asset movement', function () {
    $movement = AssetMovement::factory()->create();

    $response = getJson("/api/asset-movements/{$movement->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $movement->id);
});

test('it updates existing asset movement', function () {
    $movement = AssetMovement::factory()->create();

    $response = putJson("/api/asset-movements/{$movement->id}", [
        'moved_at' => now()->format('Y-m-d H:i:s'),
        'notes' => 'Updated notes',
    ]);

    $response->assertStatus(200);
    assertDatabaseHas('asset_movements', [
        'id' => $movement->id,
        'notes' => 'Updated notes',
    ]);
});

test('it deletes asset movement', function () {
    $movement = AssetMovement::factory()->create();

    $response = deleteJson("/api/asset-movements/{$movement->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('asset_movements', ['id' => $movement->id]);
});

test('it stores a supporting document with the movement', function () {
    Storage::fake('public');

    $asset = Asset::factory()->create();
    $file = UploadedFile::fake()->create('movement-proof.pdf', 128, 'application/pdf');

    $response = post('/api/asset-movements', [
        'asset_id' => $asset->id,
        'movement_type' => 'adjustment',
        'moved_at' => now()->format('Y-m-d H:i:s'),
        'document' => $file,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.document_name', 'movement-proof.pdf');

    $path = AssetMovement::latest('id')->value('document_path');
    expect($path)->not->toBeNull();
    Storage::disk('public')->assertExists($path);
});

test('it rejects unsupported document types', function () {
    Storage::fake('public');

    $asset = Asset::factory()->create();

    post('/api/asset-movements', [
        'asset_id' => $asset->id,
        'movement_type' => 'adjustment',
        'moved_at' => now()->format('Y-m-d H:i:s'),
        'document' => UploadedFile::fake()->create('malware.exe', 128),
    ], ['Accept' => 'application/json'])->assertStatus(422)
        ->assertJsonValidationErrors('document');
});

test('it replaces an existing document on update', function () {
    Storage::fake('public');

    $movement = AssetMovement::factory()->create([
        'document_path' => 'asset-movements/documents/old.pdf',
        'document_name' => 'old.pdf',
    ]);
    Storage::disk('public')->put('asset-movements/documents/old.pdf', 'old');

    $response = post("/api/asset-movements/{$movement->id}", [
        '_method' => 'PUT',
        'moved_at' => now()->format('Y-m-d H:i:s'),
        'document' => UploadedFile::fake()->create('replacement.pdf', 128, 'application/pdf'),
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.document_name', 'replacement.pdf');

    expect($response->json('data.document_url'))
        ->toContain('/storage/asset-movements/documents/');

    Storage::disk('public')->assertMissing('asset-movements/documents/old.pdf');
    expect($movement->fresh()->document_path)->toContain('asset-movements/documents/');
});

test('it exposes a null document url when no document is attached', function () {
    $movement = AssetMovement::factory()->create();

    getJson("/api/asset-movements/{$movement->id}")
        ->assertStatus(200)
        ->assertJsonPath('data.document_url', null);
});
