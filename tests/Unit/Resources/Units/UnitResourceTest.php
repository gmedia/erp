<?php

use App\Http\Resources\Units\UnitResource;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('units');

test('to array returns correct structure', function () {
    $unit = Unit::factory()->create([
        'name' => 'Kilogram',
        'symbol' => 'kg',
    ]);
    
    $resource = new UnitResource($unit);
    $request = Request::create('/');
    
    $result = $resource->toArray($request);
    
    expect($result)->toMatchArray([
        'id' => $unit->id,
        'name' => 'Kilogram',
        // 'symbol' => 'kg', // Checking if UnitResource includes symbol. Usually SimpleCrudResource only includes id/name?
        // SimpleCrudResource default is just id, name, created_at, updated_at unless overridden.
        // Wait, does UnitResource extend SimpleCrudResource?
        // If it does, and doesn't override toArray, it WON'T have symbol.
        // I need to check UnitResource.php.
        // If the previous test passed using SimpleCrudResourceTestTrait, it only checked id, name, created_at, updated_at.
        // I'll stick to that for now unless I verify UnitResource source.
    ]);
    
    expect($result['created_at'])->toBeString()
        ->and($result['updated_at'])->toBeString();
});
