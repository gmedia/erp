<?php

use App\Models\SupplierBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('supplier-bills');

beforeEach(function () {
    $user = createTestUserWithPermissions(['supplier_bill']);
    Sanctum::actingAs($user, ['*']);
});

test('it exports supplier bills and returns file url', function () {
    Excel::fake();
    Storage::fake('public');
    SupplierBill::factory()->create();

    $response = postJson('/api/supplier-bills/export', [
        'status' => 'draft',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');
    expect($filename)->toContain('supplier_bills_export_');
    Excel::assertStored('exports/' . $filename, 'public');
});
