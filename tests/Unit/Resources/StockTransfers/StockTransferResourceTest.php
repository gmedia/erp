<?php

use App\Http\Resources\StockTransfers\StockTransferResource;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('stock-transfers');

test('to array returns correct structure', function () {
    $from = Warehouse::factory()->create(['name' => 'From WH']);
    $to = Warehouse::factory()->create(['name' => 'To WH']);
    $product = Product::factory()->create(['name' => 'Test Product']);
    $unit = Unit::factory()->create(['name' => 'PCS']);

    $transfer = StockTransfer::factory()->create([
        'transfer_number' => 'ST-TEST-0001',
        'from_warehouse_id' => $from->id,
        'to_warehouse_id' => $to->id,
        'status' => 'draft',
    ]);

    StockTransferItem::factory()->create([
        'stock_transfer_id' => $transfer->id,
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'quantity' => 2,
    ]);

    $transfer->load(['fromWarehouse', 'toWarehouse', 'items.product', 'items.unit']);

    $resource = new StockTransferResource($transfer);
    $request = Request::create('/');

    $result = $resource->toArray($request);

    expect($result)->toHaveKeys([
        'id',
        'transfer_number',
        'from_warehouse',
        'to_warehouse',
        'transfer_date',
        'status',
        'items',
        'created_at',
        'updated_at',
    ]);

    expect($result['transfer_number'])->toBe('ST-TEST-0001')
        ->and($result['from_warehouse']['name'])->toBe('From WH')
        ->and($result['to_warehouse']['name'])->toBe('To WH')
        ->and($result['items'])->toHaveCount(1);
});
