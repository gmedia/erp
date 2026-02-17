<?php

use App\Http\Resources\Units\UnitCollection;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('units');

test('to array transforms collection', function () {
    $units = Unit::factory()->count(3)->create();
    
    $collection = new UnitCollection($units);
    $request = Request::create('/');
    
    $result = $collection->toArray($request);
    
    expect($result)->toHaveCount(3);
    expect($result[0]['name'])->toBe($units[0]->name);
});
