<?php

use App\Models\PurchaseRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('purchase-requests');

beforeEach(function () {
    $user = createTestUserWithPermissions(['purchase_request']);
    actingAs($user);
});

test('it exports purchase requests and returns file url', function () {
    Excel::fake();
    Storage::fake('public');
    PurchaseRequest::factory()->create();

    $response = postJson('/api/purchase-requests/export', [
        'status' => 'draft',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');
    expect($filename)->toContain('purchase_requests_export_');
    Excel::assertStored('exports/' . $filename, 'public');
});
