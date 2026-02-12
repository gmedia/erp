<?php

namespace Tests\Unit\Domain\AssetMovements;

use App\Domain\AssetMovements\AssetMovementFilterService;
use App\Models\AssetMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class)->group('asset-movements');

test('it can filter by search', function () {
    AssetMovement::factory()->create(['reference' => 'REF-123']);
    AssetMovement::factory()->create(['reference' => 'OTHER']);

    $service = new AssetMovementFilterService();
    $query = AssetMovement::query();

    $service->applySearch($query, 'REF-123', ['reference']);

    expect($query->count())->toBe(1)
        ->and($query->first()->reference)->toBe('REF-123');
});

test('it can filter by movement_type', function () {
    AssetMovement::factory()->create(['movement_type' => 'transfer']);
    AssetMovement::factory()->create(['movement_type' => 'assign']);

    $service = new AssetMovementFilterService();
    $query = AssetMovement::query();

    $service->applyAdvancedFilters($query, ['movement_type' => 'transfer']);

    expect($query->count())->toBe(1)
        ->and($query->first()->movement_type)->toBe('transfer');
});

test('it can sort asset movements', function () {
    $m1 = AssetMovement::factory()->create(['moved_at' => '2023-01-01']);
    $m2 = AssetMovement::factory()->create(['moved_at' => '2023-01-02']);

    $service = new AssetMovementFilterService();
    $query = AssetMovement::query();

    $service->applySorting($query, 'moved_at', 'desc', ['moved_at']);

    $results = $query->get();
    expect($results->first()->id)->toBe($m2->id)
        ->and($results->last()->id)->toBe($m1->id);
});
