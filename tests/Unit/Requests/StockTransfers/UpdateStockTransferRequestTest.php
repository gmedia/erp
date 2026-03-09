<?php

use App\Http\Requests\StockTransfers\UpdateStockTransferRequest;
use App\Models\StockTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('stock-transfers');

test('authorize returns true', function () {
    $request = new UpdateStockTransferRequest;
    expect($request->authorize())->toBeTrue();
});

test('rules builds unique rule with current model id', function () {
    $transfer = StockTransfer::factory()->create();

    $request = Mockery::mock(UpdateStockTransferRequest::class)->makePartial();
    $request->shouldReceive('route')->with('stockTransfer')->andReturn($transfer);
    $request->shouldReceive('route')->with('id')->andReturn(null);

    $rules = $request->rules();

    expect($rules['transfer_number'])->toContain('unique:stock_transfers,transfer_number,' . $transfer->id);
});
