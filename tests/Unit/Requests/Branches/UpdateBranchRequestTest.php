<?php

use App\Http\Requests\Branches\UpdateBranchRequest;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('branches');

test('authorize returns true', function () {
    $request = new UpdateBranchRequest();
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $branch = Branch::factory()->create();

    $request = Mockery::mock(UpdateBranchRequest::class)->makePartial();
    
    $request->shouldReceive('route')
        ->with('branch')
        ->andReturn($branch);
        
    $request->shouldReceive('route')
        ->with('id')
        ->andReturn(null);

    expect($request->rules())->toEqual([
        'name' => ['sometimes', 'required', 'string', 'max:255', 'unique:branches,name,' . $branch->id],
    ]);
});
