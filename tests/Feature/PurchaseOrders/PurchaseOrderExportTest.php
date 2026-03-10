<?php

use App\Models\PurchaseOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('purchase-orders');

beforeEach(function () {
    $user = createTestUserWithPermissions(['purchase_order']);
    Sanctum::actingAs($user, ['*']);
});

test('it exports purchase orders and returns file url', function () {
    Excel::fake();
    Storage::fake('public');
    PurchaseOrder::factory()->create();

    $response = postJson('/api/purchase-orders/export', [
        'status' => 'draft',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');
    expect($filename)->toContain('purchase_orders_export_');
    Excel::assertStored('exports/' . $filename, 'public');
});
