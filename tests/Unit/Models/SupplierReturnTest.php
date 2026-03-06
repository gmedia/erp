<?php

use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierReturn;
use App\Models\SupplierReturnItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('supplier-returns');

test('supplier return has expected relationships', function () {
    $supplierReturn = SupplierReturn::factory()->withGoodsReceipt()->create();
    $item = SupplierReturnItem::factory()->create(['supplier_return_id' => $supplierReturn->id]);

    expect($supplierReturn->purchaseOrder)->toBeInstanceOf(PurchaseOrder::class)
        ->and($supplierReturn->goodsReceipt)->toBeInstanceOf(GoodsReceipt::class)
        ->and($supplierReturn->supplier)->toBeInstanceOf(Supplier::class)
        ->and($supplierReturn->warehouse)->toBeInstanceOf(Warehouse::class)
        ->and($supplierReturn->creator)->toBeInstanceOf(User::class)
        ->and($supplierReturn->items->first()?->id)->toBe($item->id);
});
