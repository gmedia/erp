<?php

use App\Domain\ApprovalFlows\ApprovalFlowFilterService;
use App\Models\ApprovalFlow;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('approval-flows');

test('applySearch adds where clause for search term by code and name', function () {
    $service = new ApprovalFlowFilterService;

    ApprovalFlow::factory()->create(['name' => 'John Doe', 'code' => 'FLOW_JOHNDOE']);
    ApprovalFlow::factory()->create(['name' => 'Jane Smith', 'code' => 'FLOW_JANESMITH']);
    ApprovalFlow::factory()->create(['name' => 'Bob Builder', 'code' => 'FLOW_BOBBUILDER']);

    $query = ApprovalFlow::query();
    $service->applySearch($query, 'john', ['name', 'code']);

    $results = $query->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('John Doe');
});

test('applyAdvancedFilters applies approvable_type filter', function () {
    $service = new ApprovalFlowFilterService;

    ApprovalFlow::factory()->create(['approvable_type' => 'App\\Models\\AssetMovement']);
    ApprovalFlow::factory()->create(['approvable_type' => 'App\\Models\\PurchaseRequest']);

    $query = ApprovalFlow::query();
    $service->applyAdvancedFilters($query, ['approvable_type' => 'App\\Models\\AssetMovement']);

    $results = $query->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->approvable_type)->toBe('App\\Models\\AssetMovement');
});

test('applyAdvancedFilters applies is_active filter', function () {
    $service = new ApprovalFlowFilterService;

    ApprovalFlow::factory()->create(['is_active' => true]);
    ApprovalFlow::factory()->create(['is_active' => false]);

    $query = ApprovalFlow::query();
    $service->applyAdvancedFilters($query, ['is_active' => 0]);

    $results = $query->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->is_active)->toBeFalse();
});

test('applyAdvancedFilters handles empty filters', function () {
    $service = new ApprovalFlowFilterService;

    ApprovalFlow::factory()->count(3)->create();

    $query = ApprovalFlow::query();
    $originalCount = $query->count();

    $service->applyAdvancedFilters($query, []);

    expect($query->count())->toBe($originalCount);
});

test('applySorting applies ascending sort when allowed', function () {
    $service = new ApprovalFlowFilterService;

    ApprovalFlow::factory()->create(['name' => 'Z Flow']);
    ApprovalFlow::factory()->create(['name' => 'A Flow']);

    $query = ApprovalFlow::query();
    $service->applySorting($query, 'name', 'asc', ['id', 'name', 'code', 'approvable_type', 'is_active', 'created_at']);

    $results = $query->get();

    expect($results->first()->name)->toBe('A Flow')
        ->and($results->last()->name)->toBe('Z Flow');
});

test('applySorting applies descending sort when allowed', function () {
    $service = new ApprovalFlowFilterService;

    ApprovalFlow::factory()->create(['name' => 'A Flow']);
    ApprovalFlow::factory()->create(['name' => 'Z Flow']);

    $query = ApprovalFlow::query();
    $service->applySorting($query, 'name', 'desc', [
        'id',
        'name',
        'code',
        'approvable_type',
        'is_active',
        'created_at',
    ]);

    $results = $query->get();

    expect($results->first()->name)->toBe('Z Flow')
        ->and($results->last()->name)->toBe('A Flow');
});
