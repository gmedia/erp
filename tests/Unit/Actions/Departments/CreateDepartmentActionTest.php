<?php

use App\Actions\Departments\CreateDepartmentAction;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

test('execute creates a new department', function () {
    $action = new CreateDepartmentAction();

    $data = [
        'name' => 'Human Resources',
    ];

    $department = $action->execute($data);

    expect($department)->toBeInstanceOf(Department::class)
        ->and($department->name)->toBe('Human Resources');

    assertDatabaseHas('departments', ['name' => 'Human Resources']);
});

test('execute creates department with additional data', function () {
    $action = new CreateDepartmentAction();

    $data = [
        'name' => 'Engineering',
        'description' => 'Software development department',
    ];

    $department = $action->execute($data);

    expect($department->name)->toBe('Engineering')
        ->and($department->description)->toBe('Software development department');
});
