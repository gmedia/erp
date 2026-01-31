<?php

use App\Http\Resources\FiscalYears\FiscalYearCollection;
use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('fiscal-years');

test('collection returns multiple resources', function () {
    FiscalYear::factory()->count(3)->create();
    
    $collection = new FiscalYearCollection(FiscalYear::all());
    $data = $collection->toArray(request());

    expect($data)->toHaveCount(3);
});
