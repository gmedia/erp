<?php

use App\Http\Resources\SupplierReturns\SupplierReturnCollection;
use App\Models\SupplierReturn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('supplier-returns');

test('supplier return collection wraps resources', function () {
    $rows = SupplierReturn::factory()->count(2)->create();
    $rows->load(['purchaseOrder', 'goodsReceipt', 'supplier', 'warehouse', 'creator', 'items.product', 'items.unit']);

    $collection = new SupplierReturnCollection($rows);
    $data = $collection->toArray(new Request());

    expect($data)->toHaveCount(2)
        ->and($data[0])->toHaveKey('id');
});
