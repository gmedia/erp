<?php

use App\Http\Resources\Departments\DepartmentCollection;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('departments');

test('to array transforms collection of departments', function () {
    $departments = Department::factory()->count(3)->create();
    
    $collection = new DepartmentCollection($departments);
    $request = Request::create('/');
    
    $result = $collection->toArray($request);
    
    expect($result)->toHaveCount(3);
    
    expect($result[0])->toHaveKeys(['id', 'name', 'created_at', 'updated_at']);
    expect($result[0]['name'])->toBe($departments[0]->name);
});
