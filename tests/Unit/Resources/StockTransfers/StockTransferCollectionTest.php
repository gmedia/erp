<?php

use App\Http\Resources\StockTransfers\StockTransferCollection;
use App\Models\StockTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('stock-transfers');

test('to array transforms collection of transfers', function () {
    $transfers = StockTransfer::factory()->count(3)->create(['status' => 'draft']);

    $collection = new StockTransferCollection($transfers);
    $request = Request::create('/');

    $result = $collection->toArray($request);

    expect($result)->toHaveCount(3);
    expect($result[0])->toHaveKeys(['id', 'transfer_number', 'from_warehouse', 'to_warehouse', 'created_at', 'updated_at']);
});

