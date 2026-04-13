<?php

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class)->group('stock-movements');

test('stock movement has expected relationships', function () {
    $product = Product::factory()->create();
    $warehouse = Warehouse::factory()->create();
    $creator = User::factory()->create();

    $movement = StockMovement::factory()->create([
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'created_by' => $creator->id,
    ]);

    expect($movement->product)->toBeInstanceOf(Product::class)
        ->and($movement->warehouse)->toBeInstanceOf(Warehouse::class)
        ->and($movement->createdBy)->toBeInstanceOf(User::class);
});

test('stock movement preserves numeric and datetime casts', function () {
    $movement = StockMovement::factory()->create([
        'quantity_in' => 10,
        'quantity_out' => 2,
        'balance_after' => 8,
        'unit_cost' => 1500,
        'average_cost_after' => 1600,
        'moved_at' => '2026-04-13 10:30:00',
    ]);

    expect($movement->quantity_in)->toBe('10.00')
        ->and($movement->quantity_out)->toBe('2.00')
        ->and($movement->balance_after)->toBe('8.00')
        ->and($movement->unit_cost)->toBe('1500.00')
        ->and($movement->average_cost_after)->toBe('1600.00')
        ->and($movement->moved_at)->toBeInstanceOf(Carbon::class)
        ->and($movement->moved_at?->toDateTimeString())->toBe('2026-04-13 10:30:00');
});
