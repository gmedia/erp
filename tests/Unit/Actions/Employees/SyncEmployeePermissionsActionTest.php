<?php

use App\Actions\Employees\SyncEmployeePermissionsAction;
use App\Models\Employee;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('employees');

test('execute syncs permission ids onto the employee', function () {
    $employee = Employee::factory()->create();
    $permissions = Permission::factory()->count(3)->create();

    (new SyncEmployeePermissionsAction)->execute(
        $employee,
        $permissions->pluck('id')->all(),
    );

    expect($employee->permissions()->pluck('permissions.id')->sort()->values()->all())
        ->toEqual($permissions->pluck('id')->sort()->values()->all());
});

test('execute replaces existing permissions with the new set', function () {
    $employee = Employee::factory()->create();
    $initial = Permission::factory()->count(2)->create();
    $replacement = Permission::factory()->create();

    $employee->permissions()->sync($initial->pluck('id')->all());

    (new SyncEmployeePermissionsAction)->execute($employee, [$replacement->id]);

    expect($employee->permissions()->pluck('permissions.id')->all())
        ->toEqual([$replacement->id]);
});

test('execute with empty array detaches all permissions', function () {
    $employee = Employee::factory()->create();
    $permissions = Permission::factory()->count(2)->create();
    $employee->permissions()->sync($permissions->pluck('id')->all());

    (new SyncEmployeePermissionsAction)->execute($employee, []);

    expect($employee->permissions()->count())->toBe(0);
});
