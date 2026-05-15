<?php

use App\Models\ReportConfiguration;
use App\Models\ReportSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('financial-reports');

beforeEach(function () {
    $this->user = createTestUserWithPermissions([]);
    Sanctum::actingAs($this->user, ['*']);
});

function reportConfigurationPayload(array $overrides = []): array
{
    return array_merge([
        'code' => 'custom_bs',
        'name' => 'Custom Balance Sheet',
        'description' => 'Customer-defined layout',
        'report_type' => ReportConfiguration::TYPE_BALANCE_SHEET,
        'is_active' => true,
        'sections' => [
            [
                'code' => 'assets_header',
                'name' => 'ASET',
                'section_type' => ReportSection::TYPE_HEADER,
                'sort_order' => 10,
            ],
            [
                'code' => 'current_assets',
                'name' => 'Aset Lancar',
                'section_type' => ReportSection::TYPE_DETAIL,
                'sort_order' => 20,
                'account_type_filter' => 'asset',
                'account_sub_type_filter' => 'current_asset',
                'parent_code' => 'assets_header',
            ],
            [
                'code' => 'total_assets',
                'name' => 'TOTAL ASET',
                'section_type' => ReportSection::TYPE_TOTAL,
                'sort_order' => 30,
                'account_type_filter' => 'asset',
            ],
        ],
    ], $overrides);
}

test('it lists report configurations with pagination', function () {
    ReportConfiguration::factory()->count(3)->create();

    getJson('/api/report-configurations')
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'code', 'name', 'report_type', 'is_active']], 'meta']);
});

test('it filters report configurations by report_type', function () {
    ReportConfiguration::factory()->ofType(ReportConfiguration::TYPE_BALANCE_SHEET)->create();
    ReportConfiguration::factory()->ofType(ReportConfiguration::TYPE_INCOME_STATEMENT)->create();

    getJson('/api/report-configurations?report_type=balance_sheet')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('it searches report configurations by code or name', function () {
    ReportConfiguration::factory()->create(['code' => 'bs_default', 'name' => 'Balance Sheet Default']);
    ReportConfiguration::factory()->create(['code' => 'is_default', 'name' => 'Income Statement']);

    getJson('/api/report-configurations?search=Balance')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.code', 'bs_default');
});

test('it creates a report configuration with sections', function () {
    $response = postJson('/api/report-configurations', reportConfigurationPayload());

    $response->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'code', 'sections' => [['id', 'code', 'parent_id']]]])
        ->assertJsonPath('data.code', 'custom_bs');

    assertDatabaseHas('report_configurations', ['code' => 'custom_bs']);
    expect(ReportConfiguration::where('code', 'custom_bs')->first()->sections)->toHaveCount(3);
});

test('it resolves parent_code to parent_id when creating sections', function () {
    postJson('/api/report-configurations', reportConfigurationPayload())->assertCreated();

    $config = ReportConfiguration::where('code', 'custom_bs')->first();
    $parent = $config->sections->firstWhere('code', 'assets_header');
    $child = $config->sections->firstWhere('code', 'current_assets');

    expect($child->parent_id)->toBe($parent->id);
});

test('it shows a report configuration with sections', function () {
    $config = ReportConfiguration::factory()->create();
    ReportSection::factory()->count(2)->create(['report_configuration_id' => $config->id]);

    getJson("/api/report-configurations/{$config->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $config->id)
        ->assertJsonStructure(['data' => ['sections' => [['id', 'code', 'section_type']]]]);
});

test('it updates a report configuration and replaces sections', function () {
    $config = ReportConfiguration::factory()->create(['code' => 'old_code']);
    ReportSection::factory()->count(5)->create(['report_configuration_id' => $config->id]);

    $payload = reportConfigurationPayload(['code' => 'old_code', 'name' => 'Updated Name']);

    putJson("/api/report-configurations/{$config->id}", $payload)->assertOk();

    assertDatabaseHas('report_configurations', ['id' => $config->id, 'name' => 'Updated Name']);
    expect($config->refresh()->sections)->toHaveCount(3);
});

test('it deletes a report configuration and cascades sections', function () {
    $config = ReportConfiguration::factory()->create();
    ReportSection::factory()->count(2)->create(['report_configuration_id' => $config->id]);

    deleteJson("/api/report-configurations/{$config->id}")->assertNoContent();

    assertDatabaseMissing('report_configurations', ['id' => $config->id]);
    assertDatabaseMissing('report_sections', ['report_configuration_id' => $config->id]);
});

test('it rejects duplicate codes', function () {
    ReportConfiguration::factory()->create(['code' => 'bs_default']);

    postJson('/api/report-configurations', reportConfigurationPayload(['code' => 'bs_default']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('code');
});

test('it rejects invalid report_type', function () {
    postJson('/api/report-configurations', reportConfigurationPayload(['report_type' => 'unknown_type']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('report_type');
});

test('it rejects invalid section_type', function () {
    $payload = reportConfigurationPayload();
    $payload['sections'][0]['section_type'] = 'bogus';

    postJson('/api/report-configurations', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('sections.0.section_type');
});

test('it sorts by name by default', function () {
    ReportConfiguration::factory()->create(['name' => 'Alpha']);
    ReportConfiguration::factory()->create(['name' => 'Bravo']);

    $response = getJson('/api/report-configurations?sort_by=name&sort_direction=asc')->assertOk();

    expect($response->json('data.0.name'))->toBe('Alpha');
});
