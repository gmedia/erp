<?php

use App\Domain\Branches\BranchFilterService;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('branches');

test('apply search filters by name', function () {
    Branch::factory()->create(['name' => 'HQ']);
    Branch::factory()->create(['name' => 'Branch 2']);

    $service = new BranchFilterService();
    $query = Branch::query();
    
    $service->applySearch($query, 'HQ', ['name']);
    
    expect($query->count())->toBe(1)
        ->and($query->first()->name)->toBe('HQ');
});
