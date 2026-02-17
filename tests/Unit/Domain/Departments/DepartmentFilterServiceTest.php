<?php

use App\Domain\Departments\DepartmentFilterService;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('departments');

test('apply search filters by name', function () {
    Department::factory()->create(['name' => 'IT Dept']);
    Department::factory()->create(['name' => 'HR Dept']);

    $service = new DepartmentFilterService();
    $query = Department::query();
    
    $service->applySearch($query, 'IT', ['name']);
    
    expect($query->count())->toBe(1)
        ->and($query->first()->name)->toBe('IT Dept');
});
