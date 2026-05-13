<?php

use App\Models\ReportConfiguration;
use App\Models\ReportSection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('financial-reports');

test('it has correct fillable attributes', function () {
    expect((new ReportSection)->getFillable())->toBe([
        'report_configuration_id',
        'parent_id',
        'code',
        'name',
        'sort_order',
        'section_type',
        'account_type_filter',
        'account_sub_type_filter',
        'sign_convention',
        'formula',
        'is_active',
    ]);
});

test('it casts sort_order to integer and is_active to boolean', function () {
    $section = ReportSection::factory()->create([
        'sort_order' => '15',
        'is_active' => 1,
    ]);

    expect($section->sort_order)->toBe(15)
        ->and($section->is_active)->toBeTrue();
});

test('it has reportConfiguration, parent, and children relations', function () {
    $section = new ReportSection;

    expect($section->reportConfiguration())->toBeInstanceOf(BelongsTo::class)
        ->and($section->parent())->toBeInstanceOf(BelongsTo::class)
        ->and($section->children())->toBeInstanceOf(HasMany::class);
});

test('children are ordered by sort_order', function () {
    $config = ReportConfiguration::factory()->create();
    $parent = ReportSection::factory()->create(['report_configuration_id' => $config->id, 'code' => 'p']);
    ReportSection::factory()->create(['report_configuration_id' => $config->id, 'parent_id' => $parent->id, 'sort_order' => 20, 'code' => 'c2']);
    ReportSection::factory()->create(['report_configuration_id' => $config->id, 'parent_id' => $parent->id, 'sort_order' => 10, 'code' => 'c1']);

    expect($parent->children()->pluck('code')->toArray())->toBe(['c1', 'c2']);
});

test('active scope filters to active rows', function () {
    $config = ReportConfiguration::factory()->create();
    ReportSection::factory()->create(['report_configuration_id' => $config->id, 'is_active' => true, 'code' => 'a']);
    ReportSection::factory()->create(['report_configuration_id' => $config->id, 'is_active' => false, 'code' => 'b']);

    expect(ReportSection::active()->count())->toBe(1);
});

test('code is unique per configuration', function () {
    $config = ReportConfiguration::factory()->create();
    ReportSection::factory()->create(['report_configuration_id' => $config->id, 'code' => 'current_assets']);

    expect(fn () => ReportSection::factory()->create(['report_configuration_id' => $config->id, 'code' => 'current_assets']))
        ->toThrow(Illuminate\Database\QueryException::class);
});

test('same code can exist across different configurations', function () {
    $configA = ReportConfiguration::factory()->create();
    $configB = ReportConfiguration::factory()->create();
    ReportSection::factory()->create(['report_configuration_id' => $configA->id, 'code' => 'current_assets']);
    ReportSection::factory()->create(['report_configuration_id' => $configB->id, 'code' => 'current_assets']);

    expect(ReportSection::where('code', 'current_assets')->count())->toBe(2);
});

test('deleting a parent section nullifies its childrens parent_id', function () {
    $config = ReportConfiguration::factory()->create();
    $parent = ReportSection::factory()->create(['report_configuration_id' => $config->id, 'code' => 'parent']);
    $child = ReportSection::factory()->create(['report_configuration_id' => $config->id, 'parent_id' => $parent->id, 'code' => 'child']);

    $parent->delete();

    expect($child->fresh()->parent_id)->toBeNull();
});
