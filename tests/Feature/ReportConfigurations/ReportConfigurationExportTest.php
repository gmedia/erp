<?php

use App\Models\ReportConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('financial-reports');

test('it exports report configurations to xlsx', function () {
    Excel::fake();
    Storage::fake('public');
    Sanctum::actingAs(createTestUserWithPermissions(['report_configuration']), ['*']);
    ReportConfiguration::factory()->count(2)->create();

    $response = postJson('/api/report-configurations/export', [])->assertOk();

    $response->assertJsonStructure(['url', 'filename']);
    expect($response->json('filename'))->toEndWith('.xlsx');
});

test('it applies filters to export', function () {
    Excel::fake();
    Storage::fake('public');
    Sanctum::actingAs(createTestUserWithPermissions(['report_configuration']), ['*']);
    ReportConfiguration::factory()->ofType(ReportConfiguration::TYPE_BALANCE_SHEET)->create();
    ReportConfiguration::factory()->ofType(ReportConfiguration::TYPE_INCOME_STATEMENT)->create();

    postJson('/api/report-configurations/export', ['report_type' => 'balance_sheet'])
        ->assertOk()
        ->assertJsonStructure(['url', 'filename']);
});
