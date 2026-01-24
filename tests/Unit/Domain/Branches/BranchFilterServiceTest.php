<?php

use App\Domain\Branches\BranchFilterService;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('branches');

test('applySearch adds where clause for search term', function () {
    $service = new BranchFilterService;

    Branch::factory()->create(['name' => 'Jakarta Branch']);
    Branch::factory()->create(['name' => 'Surabaya Branch']);
    Branch::factory()->create(['name' => 'Bandung Branch']);

    $query = Branch::query();
    $service->applySearch($query, 'jakarta', ['name']);

    $results = $query->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('Jakarta Branch');
});

test('applySearch searches across multiple fields', function () {
    $service = new BranchFilterService;

    Branch::factory()->create(['name' => 'Jakarta Central']);
    Branch::factory()->create(['name' => 'Surabaya Central']);

    $query = Branch::query();
    $service->applySearch($query, 'Central', ['name']);

    $results = $query->get();

    expect($results)->toHaveCount(2);
});

test('applySorting applies ascending sort when allowed', function () {
    $service = new BranchFilterService;

    Branch::factory()->create(['name' => 'Zeta Branch']);
    Branch::factory()->create(['name' => 'Alpha Branch']);

    $query = Branch::query();
    $service->applySorting($query, 'name', 'asc', ['id', 'name', 'created_at', 'updated_at']);

    $results = $query->get();

    expect($results->first()->name)->toBe('Alpha Branch')
        ->and($results->last()->name)->toBe('Zeta Branch');
});

test('applySorting applies descending sort when allowed', function () {
    $service = new BranchFilterService;

    Branch::factory()->create(['name' => 'Alpha Branch']);
    Branch::factory()->create(['name' => 'Zeta Branch']);

    $query = Branch::query();
    $service->applySorting($query, 'name', 'desc', ['id', 'name', 'created_at', 'updated_at']);

    $results = $query->get();

    expect($results->first()->name)->toBe('Zeta Branch')
        ->and($results->last()->name)->toBe('Alpha Branch');
});

test('applySorting does not apply sort when field not allowed', function () {
    $service = new BranchFilterService;

    Branch::factory()->create(['name' => 'Test Branch']);

    $query = Branch::query();
    $originalSql = $query->toSql();

    $service->applySorting($query, 'invalid_field', 'asc', ['id', 'name', 'created_at', 'updated_at']);

    // SQL should remain unchanged since invalid field
    expect($query->toSql())->toBe($originalSql);
});
