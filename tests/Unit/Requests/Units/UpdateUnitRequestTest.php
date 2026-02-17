<?php

use App\Http\Requests\Units\UpdateUnitRequest;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('units');

test('authorize returns true', function () {
    $request = new UpdateUnitRequest();
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $unit = Unit::factory()->create();

    $request = Mockery::mock(UpdateUnitRequest::class)->makePartial();
    
    $request->shouldReceive('route')
        ->with('unit')
        ->andReturn($unit);
        
    $request->shouldReceive('route')
        ->with('id')
        ->andReturn(null);

    expect($request->rules())->toEqual([
        'name' => ['sometimes', 'required', 'string', 'max:255', 'unique:units,name,' . $unit->id],
        'symbol' => 'nullable|string|max:10',
    ]);
});
