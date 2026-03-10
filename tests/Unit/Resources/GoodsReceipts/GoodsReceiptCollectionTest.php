<?php

use App\Http\Resources\GoodsReceipts\GoodsReceiptCollection;
use App\Models\GoodsReceipt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('goods-receipts');

test('goods receipt collection wraps resources', function () {
    $rows = GoodsReceipt::factory()->count(2)->create();
    $rows->load(['purchaseOrder.supplier', 'warehouse', 'receiver', 'confirmer', 'creator', 'items.product', 'items.unit']);

    $collection = new GoodsReceiptCollection($rows);
    $data = $collection->toArray(new Request);

    expect($data)->toHaveCount(2)
        ->and($data[0])->toHaveKey('id');
});
