<?php

namespace Tests\Feature;

use App\Models\AssetMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class)->group('asset-movements');

test('it can export asset movements', function () {
    \Illuminate\Support\Carbon::setTestNow('2023-01-01 00:00:00');
    
    $user = createTestUserWithPermissions(['asset_movement']);
    AssetMovement::factory()->count(3)->create();

    Excel::fake();

    $response = $this->actingAs($user)
        ->post(route('api.asset-movements.export'));

    $response->assertOk();

    Excel::assertStored('exports/asset-movements-export-2023-01-01-00-00-00.xlsx', 'public');
});
