<?php

use App\Http\Resources\Branches\BranchResource;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('branches', 'resources');

test('to array returns correct structure', function () {
    $branch = Branch::factory()->create(['name' => 'Main Branch']);
    
    $resource = new BranchResource($branch);
    $request = Request::create('/');
    
    $result = $resource->toArray($request);
    
    expect($result)->toMatchArray([
        'id' => $branch->id,
        'name' => 'Main Branch',
    ]);
    
    expect($result['created_at'])->toBeString()
        ->and($result['updated_at'])->toBeString();
});
