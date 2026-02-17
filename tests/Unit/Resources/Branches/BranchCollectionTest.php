<?php

use App\Http\Resources\Branches\BranchCollection;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('branches');

test('to array transforms collection', function () {
    $branches = Branch::factory()->count(3)->create();
    
    $collection = new BranchCollection($branches);
    $request = Request::create('/');
    
    $result = $collection->toArray($request);
    
    expect($result)->toHaveCount(3);
    expect($result[0]['name'])->toBe($branches[0]->name);
});
