<?php

namespace Tests\Feature\AssetMovements;

use App\Models\AssetMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('asset-movements');

beforeEach(function () {
    $user = createTestUserWithPermissions(['asset_movement', 'asset_movement.export']);
    actingAs($user);
});

test('it can export asset movements', function () {
    Carbon::setTestNow('2023-01-01 00:00:00');
    
    AssetMovement::factory()->count(3)->create();

    Excel::fake();

    $response = postJson('/api/asset-movements/export');

    $response->assertOk();

    Excel::assertStored('exports/asset-movements-export-2023-01-01-00-00-00.xlsx', 'public');
});
