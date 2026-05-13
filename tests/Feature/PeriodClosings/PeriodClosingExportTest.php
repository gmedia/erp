<?php

use App\Models\PeriodClosing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('period-closings');

test('it exports period closings to xlsx', function () {
    Sanctum::actingAs(createTestUserWithPermissions(['period_closing']), ['*']);
    PeriodClosing::factory()->count(2)->create();

    $response = postJson('/api/period-closings/export', [])->assertOk()->assertJsonStructure(['url', 'filename']);

    expect($response->json('filename'))->toEndWith('.xlsx');
});
