<?php

use App\Models\FiscalYear;
use App\Models\ReportConfiguration;
use App\Models\ReportSection;
use Database\Seeders\ReportConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class)->group('financial-reports');

beforeEach(function () {
    $this->user = createTestUserWithPermissions([
        'trial_balance_report',
        'balance_sheet_report',
        'income_statement_report',
        'cash_flow_report',
    ]);
    $this->seed(ReportConfigurationSeeder::class);
    FiscalYear::create([
        'name' => '2025',
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
        'status' => 'open',
    ]);
    Sanctum::actingAs($this->user, ['*']);
});

test('balance sheet response includes DB-driven configuration payload', function () {
    $this->getJson('/api/reports/balance-sheet')
        ->assertStatus(200)
        ->assertJsonPath('configuration.code', 'balance_sheet')
        ->assertJsonPath('configuration.report_type', ReportConfiguration::TYPE_BALANCE_SHEET)
        ->assertJsonStructure(['configuration' => ['sections' => [['code', 'name', 'section_type']]]]);
});

test('income statement response includes configuration with ordered sections', function () {
    $response = $this->getJson('/api/reports/income-statement')->assertStatus(200);

    $sections = $response->json('configuration.sections');
    expect($sections[0]['sort_order'])->toBeLessThan($sections[1]['sort_order'])
        ->and(collect($sections)->pluck('code'))->toContain('total_revenue', 'net_income');
});

test('cash flow response includes configuration with reversed sign section for depreciation', function () {
    $response = $this->getJson('/api/reports/cash-flow')->assertStatus(200);

    $sections = collect($response->json('configuration.sections'));
    expect($sections->firstWhere('code', 'depreciation_addback')['sign_convention'])
        ->toBe(ReportSection::SIGN_REVERSED);
});

test('trial balance response includes minimal configuration', function () {
    $this->getJson('/api/reports/trial-balance')
        ->assertStatus(200)
        ->assertJsonPath('configuration.code', 'trial_balance');
});

test('configuration is null when report_configuration row is missing', function () {
    ReportConfiguration::query()->delete();

    $this->getJson('/api/reports/balance-sheet')
        ->assertStatus(200)
        ->assertJsonPath('configuration', null);
});

test('configuration skips inactive sections', function () {
    $config = ReportConfiguration::where('code', 'balance_sheet')->firstOrFail();
    $activeCount = $config->sections()->where('is_active', true)->count();
    $config->sections()->limit(1)->update(['is_active' => false]);

    $response = $this->getJson('/api/reports/balance-sheet')->assertStatus(200);

    expect($response->json('configuration.sections'))->toHaveCount($activeCount - 1);
});
