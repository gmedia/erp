<?php

use App\Http\Requests\Departments\UpdateDepartmentRequest;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('departments', 'requests');

test('authorize returns true', function () {
    $request = new UpdateDepartmentRequest();
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $department = Department::factory()->create();

    // Partially mock the Request to override the route method
    $request = Mockery::mock(UpdateDepartmentRequest::class)->makePartial();
    
    // Mock the route method to return the department model when 'department' is requested
    $request->shouldReceive('route')
        ->with('department')
        ->andReturn($department);
        
    // Also likely need to handle if logic calls route('id') or other params if any
    $request->shouldReceive('route')
        ->with('id')
        ->andReturn(null);

    expect($request->rules())->toEqual([
        'name' => ['sometimes', 'required', 'string', 'max:255', 'unique:departments,name,' . $department->id],
    ]);
});
