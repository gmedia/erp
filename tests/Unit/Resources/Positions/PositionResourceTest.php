<?php

use App\Http\Resources\Positions\PositionResource;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('positions');

test('to array returns correct structure', function () {
    $position = Position::factory()->create([
        'name' => 'Manager',
    ]);
    
    $resource = new PositionResource($position);
    $request = Request::create('/');
    
    $result = $resource->toArray($request);
    
    expect($result)->toMatchArray([
        'id' => $position->id,
        'name' => 'Manager',
    ]);
    
    expect($result['created_at'])->toBeString()
        ->and($result['updated_at'])->toBeString();
});
