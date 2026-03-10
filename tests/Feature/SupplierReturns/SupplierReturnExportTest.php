<?php

use App\Models\SupplierReturn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('supplier-returns');

beforeEach(function () {
    $user = createTestUserWithPermissions(['supplier_return']);
    Sanctum::actingAs($user, ['*']);
});

test('it exports supplier returns and returns file url', function () {
    Excel::fake();
    Storage::fake('public');
    SupplierReturn::factory()->create();

    $response = postJson('/api/supplier-returns/export', [
        'status' => 'draft',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');
    expect($filename)->toContain('supplier_returns_export_');
    Excel::assertStored('exports/' . $filename, 'public');
});
