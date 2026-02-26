<?php

use App\Http\Resources\InventoryStocktakes\InventoryStocktakeCollection;
use App\Models\InventoryStocktake;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('inventory-stocktakes');

test('collection wraps data correctly', function () {
    $stocktakes = InventoryStocktake::factory()->count(2)->create();

    $collection = new InventoryStocktakeCollection($stocktakes);
    $request = Request::create('/');

    $result = $collection->toArray($request);

    expect($result)->toHaveCount(2);
    expect($result[0])->toHaveKeys(['id', 'stocktake_number', 'warehouse', 'stocktake_date', 'status', 'created_at', 'updated_at']);
});
