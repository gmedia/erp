<?php

use App\Http\Resources\FiscalYears\FiscalYearResource;
use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('fiscal-years');

test('resource returns correct fields', function () {
    $fiscalYear = FiscalYear::factory()->create([
        'name' => 'FY 2025',
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
        'status' => 'open',
    ]);

    $resource = new FiscalYearResource($fiscalYear);
    $data = $resource->toArray(request());

    expect($data)->toHaveKeys(['id', 'name', 'start_date', 'end_date', 'status', 'created_at', 'updated_at'])
        ->and($data['name'])->toBe('FY 2025')
        ->and($data['start_date'])->toBe('2025-01-01')
        ->and($data['end_date'])->toBe('2025-12-31')
        ->and($data['status'])->toBe('open');
});
