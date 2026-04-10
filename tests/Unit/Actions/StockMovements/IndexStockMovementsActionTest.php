<?php

use App\Actions\StockMovements\IndexStockMovementsAction;
use App\Http\Requests\StockMovements\IndexStockMovementRequest;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;

uses(RefreshDatabase::class)->group('stock-movements');

test('it returns paginated stock movements', function () {
    $product = Product::factory()->create();
    $warehouse = Warehouse::factory()->create();

    StockMovement::factory()->count(2)->create([
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
    ]);

    $action = app(IndexStockMovementsAction::class);
    $request = new IndexStockMovementRequest(['per_page' => 1]);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->total())->toBe(2)
        ->and($result->perPage())->toBe(1);
});

test('it returns filtered stock movements collection when export requested', function () {
    $product = Product::factory()->create();
    $warehouse = Warehouse::factory()->create();

    StockMovement::factory()->create([
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'movement_type' => 'transfer_in',
    ]);

    StockMovement::factory()->create([
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'movement_type' => 'transfer_out',
    ]);

    $action = app(IndexStockMovementsAction::class);
    $request = new IndexStockMovementRequest([
        'export' => true,
        'movement_type' => 'transfer_in',
    ]);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount(1)
        ->and($result->first()->movement_type)->toBe('transfer_in');
});
