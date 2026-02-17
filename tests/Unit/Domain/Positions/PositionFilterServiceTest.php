<?php

use App\Domain\Positions\PositionFilterService;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('positions');

test('apply search filters by name', function () {
    Position::factory()->create(['name' => 'Manager']);
    Position::factory()->create(['name' => 'Staff']);

    $service = new PositionFilterService();
    $query = Position::query();
    
    $service->applySearch($query, 'Man', ['name']);
    
    expect($query->count())->toBe(1)
        ->and($query->first()->name)->toBe('Manager');
});
