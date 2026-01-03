<?php

use App\Actions\Departments\UpdateDepartmentAction;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('execute updates department name', function () {
    $action = new UpdateDepartmentAction();

    $department = Department::factory()->create([
        'name' => 'Old Name',
    ]);

    $data = [
        'name' => 'New Name',
    ];

    $updatedDepartment = $action->execute($department, $data);

    expect($updatedDepartment->name)->toBe('New Name')
        ->and($updatedDepartment->id)->toBe($department->id);

    $department->refresh();
    expect($department->name)->toBe('New Name');
});

test('execute updates department to new name', function () {
    $action = new UpdateDepartmentAction();

    $department = Department::factory()->create([
        'name' => 'Engineering',
    ]);

    $data = [
        'name' => 'Software Engineering',
    ];

    $updatedDepartment = $action->execute($department, $data);

    expect($updatedDepartment->name)->toBe('Software Engineering');
});

test('execute returns fresh model instance', function () {
    $action = new UpdateDepartmentAction();

    $department = Department::factory()->create();

    $data = [
        'name' => 'Updated Name',
    ];

    $updatedDepartment = $action->execute($department, $data);

    expect($updatedDepartment)->toBeInstanceOf(Department::class)
        ->and($updatedDepartment)->not->toBe($department); // Different instance due to fresh()
});
