<?php

use App\Http\Resources\Positions\PositionCollection;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('positions');

test('to array transforms collection of positions', function () {
    $positions = Position::factory()->count(3)->create();
    
    $collection = new PositionCollection($positions);
    $request = Request::create('/');
    
    $result = $collection->toArray($request);
    
    expect($result)->toHaveCount(3);
    
    expect($result[0])->toHaveKeys(['id', 'name', 'created_at', 'updated_at']);
    expect($result[0]['name'])->toBe($positions[0]->name);
});
