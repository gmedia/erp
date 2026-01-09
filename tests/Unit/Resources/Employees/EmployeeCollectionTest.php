<?php

use App\Http\Resources\Employees\EmployeeCollection;
use App\Http\Resources\Employees\EmployeeResource;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

test('collects property is set correctly', function () {
    $collection = new EmployeeCollection([]);

    expect($collection->collects)->toBe(EmployeeResource::class);
});

test('collection transforms multiple employees correctly', function () {
    $employees = Employee::factory()->count(3)->create();

    $collection = new EmployeeCollection($employees);
    $request = new Request;

    $result = $collection->toArray($request);

    expect($result)->toBeArray()
        ->and($result)->toHaveCount(3);

    foreach ($result as $index => $item) {
        expect($item)->toHaveKeys([
            'id', 'name', 'email', 'phone', 'department',
            'position', 'salary', 'hire_date', 'created_at', 'updated_at'
        ])
            ->and($item['id'])->toBe($employees[$index]->id)
            ->and($item['name'])->toBe($employees[$index]->name)
            ->and($item['email'])->toBe($employees[$index]->email)
            ->and($item['salary'])->toBeString();
    }
});

test('collection returns empty array when no employees', function () {
    $collection = new EmployeeCollection(collect());
    $request = new Request;

    $result = $collection->toArray($request);

    expect($result)->toBeArray()
        ->and($result)->toHaveCount(0);
});
