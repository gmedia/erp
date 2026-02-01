<?php

use App\Domain\Units\UnitFilterService;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('units', 'domain');

test('apply search filters by name', function () {
    Unit::factory()->create(['name' => 'Kilogram']);
    Unit::factory()->create(['name' => 'Meter']);

    $service = new UnitFilterService();
    $query = Unit::query();
    
    $service->applySearch($query, 'Kilo', ['name']);
    
    expect($query->count())->toBe(1)
        ->and($query->first()->name)->toBe('Kilogram');
});
