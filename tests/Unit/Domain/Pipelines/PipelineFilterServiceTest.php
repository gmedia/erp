<?php

use App\Domain\Pipelines\PipelineFilterService;
use App\Models\Pipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('pipelines');

test('applyAdvancedFilters applies entity_type filter', function () {
    $service = new PipelineFilterService;

    Pipeline::factory()->create(['entity_type' => 'App\\Models\\Asset']);
    Pipeline::factory()->create(['entity_type' => 'App\\Models\\PurchaseOrder']);

    $query = Pipeline::query();
    $service->applyAdvancedFilters($query, ['entity_type' => 'App\\Models\\Asset']);

    expect($query->count())->toBe(1)
        ->and($query->first()->entity_type)->toBe('App\\Models\\Asset');
});

test('applyAdvancedFilters applies is_active filter', function () {
    $service = new PipelineFilterService;

    Pipeline::factory()->create(['is_active' => true]);
    Pipeline::factory()->create(['is_active' => false]);

    $query = Pipeline::query();
    $service->applyAdvancedFilters($query, ['is_active' => 'false']);

    expect($query->count())->toBe(1)
        ->and($query->first()->is_active)->toBeFalse();
});

test('applyAdvancedFilters handles empty filters', function () {
    $service = new PipelineFilterService;

    Pipeline::factory()->count(3)->create();

    $query = Pipeline::query();
    $originalCount = $query->count();

    $service->applyAdvancedFilters($query, []);

    expect($query->count())->toBe($originalCount);
});
