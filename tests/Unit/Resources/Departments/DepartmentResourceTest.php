<?php

use App\Http\Resources\Departments\DepartmentResource;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('departments');

test('to array returns correct structure', function () {
    $department = Department::factory()->create([
        'name' => 'IT Department',
    ]);
    
    $resource = new DepartmentResource($department);
    $request = Request::create('/');
    
    $result = $resource->toArray($request);
    
    expect($result)->toMatchArray([
        'id' => $department->id,
        'name' => 'IT Department',
    ]);
    
    expect($result['created_at'])->toBeString()
        ->and($result['updated_at'])->toBeString();
});
