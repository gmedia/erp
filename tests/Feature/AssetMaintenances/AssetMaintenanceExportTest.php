<?php

namespace Tests\Feature\AssetMaintenances;

use App\Models\AssetMaintenance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('asset-maintenances');

beforeEach(function () {
    $user = createTestUserWithPermissions(['asset_maintenance']);
    actingAs($user);
});

test('it can export asset maintenances', function () {
    Carbon::setTestNow('2023-01-01 00:00:00');

    AssetMaintenance::factory()->count(3)->create();

    Excel::fake();

    $response = postJson('/api/asset-maintenances/export');

    $response->assertOk();

    Excel::assertStored('exports/asset-maintenances-export-2023-01-01-00-00-00.xlsx', 'public');
});
