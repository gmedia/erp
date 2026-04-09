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

    $request = new UpdateStockTransferRequest;
    $request->setRouteResolver(function () use ($transfer) {
        return new class($transfer)
        {
            public function __construct(public $stockTransfer)
            {
                // No-op constructor for promoted property in anonymous route model stub.
            }

            public function parameter($key, $default = null)
            {
                return match ($key) {
                    'stockTransfer' => $this->stockTransfer,
                    'id' => null,
                    default => $default,
                };
            }
        };
    });

    $rules = $request->rules();

    expect($rules['transfer_number'])->toContain('unique:stock_transfers,transfer_number,' . $transfer->id);
});
