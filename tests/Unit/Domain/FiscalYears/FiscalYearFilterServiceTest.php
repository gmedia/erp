<?php

use App\Domain\FiscalYears\FiscalYearFilterService;
use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('fiscal-years');

test('it filters by name search', function () {
    FiscalYear::factory()->create(['name' => 'FY 2025']);
    FiscalYear::factory()->create(['name' => 'FY 2026']);

    $service = new FiscalYearFilterService();
    $query = FiscalYear::query();
    
    $service->applySearch($query, '2025', ['name']);
    
    expect($query->count())->toBe(1)
        ->and($query->first()->name)->toBe('FY 2025');
});

test('it filters by status', function () {
    FiscalYear::factory()->create(['name' => 'Open FY', 'status' => 'open']);
    FiscalYear::factory()->create(['name' => 'Closed FY', 'status' => 'closed']);

    $service = new FiscalYearFilterService();
    
    $openQuery = FiscalYear::query();
    $service->applyAdvancedFilters($openQuery, ['status' => 'open']);
    expect($openQuery->count())->toBe(1);

    $closedQuery = FiscalYear::query();
    $service->applyAdvancedFilters($closedQuery, ['status' => 'closed']);
    expect($closedQuery->count())->toBe(1);
});

test('it applies sorting', function () {
    FiscalYear::factory()->create(['name' => 'B Year']);
    FiscalYear::factory()->create(['name' => 'A Year']);

    $service = new FiscalYearFilterService();
    $query = FiscalYear::query();
    
    $service->applySorting($query, 'name', 'asc', ['name']);
    
    expect($query->first()->name)->toBe('A Year');
});
