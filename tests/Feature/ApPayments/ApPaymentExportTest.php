<?php

use App\Models\ApPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('ap-payments');

beforeEach(function () {
    $user = createTestUserWithPermissions(['ap_payment']);
    Sanctum::actingAs($user, ['*']);
});

test('it exports ap payments and returns file url', function () {
    Excel::fake();
    Storage::fake('public');
    ApPayment::factory()->create();

    $response = postJson('/api/ap-payments/export', [
        'status' => 'draft',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');
    expect($filename)->toContain('ap_payments_export_');
    Excel::assertStored('exports/' . $filename, 'public');
});
