<?php

use App\Http\Requests\Positions\UpdatePositionRequest;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('positions');

test('authorize returns true', function () {
    $request = new UpdatePositionRequest();
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $position = Position::factory()->create();

    $request = Mockery::mock(UpdatePositionRequest::class)->makePartial();
    
    $request->shouldReceive('route')
        ->with('position')
        ->andReturn($position);
        
    $request->shouldReceive('route')
        ->with('id')
        ->andReturn(null);

    expect($request->rules())->toEqual([
        'name' => ['sometimes', 'required', 'string', 'max:255', 'unique:positions,name,' . $position->id],
    ]);
});
