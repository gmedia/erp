<?php

use App\Models\Budget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('budgets');

test('it exports budgets to xlsx', function () {
    Carbon::setTestNow(Carbon::parse('2026-05-11 10:00:00'));
    Excel::fake();
    Storage::fake('public');
    Sanctum::actingAs(createTestUserWithPermissions(['budget']), ['*']);
    Budget::factory()->count(3)->create();

    $response = postJson('/api/budgets/export', [])->assertOk()->assertJsonStructure(['url', 'filename']);

    expect($response->json('filename'))->toEndWith('.xlsx');
    Carbon::setTestNow();
});

test('it applies filters to export', function () {
    Carbon::setTestNow(Carbon::parse('2026-05-11 10:00:00'));
    Excel::fake();
    Storage::fake('public');
    Sanctum::actingAs(createTestUserWithPermissions(['budget']), ['*']);
    Budget::factory()->create(['status' => 'draft']);
    Budget::factory()->approved()->create();

    postJson('/api/budgets/export', ['status' => 'draft'])
        ->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    Carbon::setTestNow();
});
