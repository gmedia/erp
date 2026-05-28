<?php

use App\Models\ReportConfiguration;
use App\Models\ReportSection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('financial-reports');

test('it has correct fillable attributes', function () {
    expect((new ReportConfiguration)->getFillable())->toBe([
        'code',
        'name',
        'description',
        'report_type',
        'layout_config',
        'is_active',
        'created_by',
    ]);
});

test('it casts layout_config to array and is_active to boolean', function () {
    $config = ReportConfiguration::factory()->create([
        'layout_config' => ['columns' => ['code', 'name']],
        'is_active' => 1,
    ]);

    expect($config->layout_config)->toBe(['columns' => ['code', 'name']])
        ->and($config->is_active)->toBeTrue();
});

test('it has a creator relation', function () {
    expect((new ReportConfiguration)->creator())->toBeInstanceOf(BelongsTo::class);
});

test('it has many sections ordered by sort_order', function () {
    expect((new ReportConfiguration)->sections())->toBeInstanceOf(HasMany::class);

    $config = ReportConfiguration::factory()->create();
    ReportSection::factory()->create(['report_configuration_id' => $config->id, 'sort_order' => 20, 'code' => 's2']);
    ReportSection::factory()->create(['report_configuration_id' => $config->id, 'sort_order' => 10, 'code' => 's1']);

    expect($config->sections()->pluck('code')->toArray())->toBe(['s1', 's2']);
});

test('active scope filters to active rows', function () {
    ReportConfiguration::factory()->create(['is_active' => true]);
    ReportConfiguration::factory()->inactive()->create();

    expect(ReportConfiguration::active()->count())->toBe(1);
});

test('ofType scope filters by report_type', function () {
    ReportConfiguration::factory()->ofType(ReportConfiguration::TYPE_BALANCE_SHEET)->create();
    ReportConfiguration::factory()->ofType(ReportConfiguration::TYPE_INCOME_STATEMENT)->create();

    expect(ReportConfiguration::ofType(ReportConfiguration::TYPE_BALANCE_SHEET)->count())->toBe(1);
});

test('code is unique', function () {
    ReportConfiguration::factory()->create(['code' => 'balance_sheet']);

    expect(fn () => ReportConfiguration::factory()->create(['code' => 'balance_sheet']))
        ->toThrow(QueryException::class);
});

test('deleting a configuration cascade deletes its sections', function () {
    $config = ReportConfiguration::factory()->create();
    ReportSection::factory()->count(3)->create(['report_configuration_id' => $config->id]);

    $config->delete();

    expect(ReportSection::where('report_configuration_id', $config->id)->count())->toBe(0);
});
