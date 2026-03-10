<?php

use App\Models\GoodsReceipt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('goods-receipts');

beforeEach(function () {
    $user = createTestUserWithPermissions(['goods_receipt']);
    Sanctum::actingAs($user, ['*']);
});

test('it exports goods receipts and returns file url', function () {
    Excel::fake();
    Storage::fake('public');
    GoodsReceipt::factory()->create();

    $response = postJson('/api/goods-receipts/export', [
        'status' => 'draft',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');
    expect($filename)->toContain('goods_receipts_export_');
    Excel::assertStored('exports/' . $filename, 'public');
});
