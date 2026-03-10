<?php

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('purchase-orders');

test('purchase order has expected relationships', function () {
    $purchaseOrder = PurchaseOrder::factory()->create();
    $item = PurchaseOrderItem::factory()->create(['purchase_order_id' => $purchaseOrder->id]);

    expect($purchaseOrder->supplier)->toBeInstanceOf(Supplier::class)
        ->and($purchaseOrder->warehouse)->toBeInstanceOf(Warehouse::class)
        ->and($purchaseOrder->creator)->toBeInstanceOf(User::class)
        ->and($purchaseOrder->items->first()?->id)->toBe($item->id);
});
