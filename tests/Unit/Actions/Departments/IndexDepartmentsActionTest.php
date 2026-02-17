<?php

use App\Actions\Departments\IndexDepartmentsAction;
use App\Http\Requests\Departments\IndexDepartmentRequest;
use App\Models\Department;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('departments');

test('execute returns paginated results', function () {
    Department::factory()->count(3)->create();

    $action = new IndexDepartmentsAction();
    $request = new IndexDepartmentRequest();
    
    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->count())->toBe(3);
});

test('execute filters by search term', function () {
    Department::factory()->create(['name' => 'IT Department']);
    Department::factory()->create(['name' => 'HR Department']);

    $action = new IndexDepartmentsAction();
    $request = new IndexDepartmentRequest(['search' => 'IT']);
    
    $result = $action->execute($request);

    expect($result->count())->toBe(1)
        ->and($result->first()->name)->toBe('IT Department');
});

test('execute sorts results', function () {
    Department::factory()->create(['name' => 'A Dept']);
    Department::factory()->create(['name' => 'B Dept']);

    $action = new IndexDepartmentsAction();
    $request = new IndexDepartmentRequest([
        'sort_by' => 'name',
        'sort_direction' => 'desc'
    ]);
    
    $result = $action->execute($request);

    expect($result->first()->name)->toBe('B Dept');
});
