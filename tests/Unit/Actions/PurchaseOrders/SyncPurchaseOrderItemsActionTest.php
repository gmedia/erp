<?php

use App\Actions\PurchaseOrders\SyncPurchaseOrderItemsAction;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('purchase-orders');

test('sync items action recalculates totals', function () {
    $purchaseOrder = PurchaseOrder::factory()->create();
    $product = Product::factory()->create();
    $unit = Unit::factory()->create();

    $action = new SyncPurchaseOrderItemsAction;
    $action->execute($purchaseOrder, [
        [
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'quantity' => 2,
            'unit_price' => 1000,
            'discount_percent' => 10,
            'tax_percent' => 11,
        ],
    ]);

    $purchaseOrder->refresh();

    expect($purchaseOrder->items)->toHaveCount(1)
        ->and((float) $purchaseOrder->subtotal)->toBe(2000.0)
        ->and((float) $purchaseOrder->discount_amount)->toBe(200.0)
        ->and((float) $purchaseOrder->tax_amount)->toBe(198.0)
        ->and((float) $purchaseOrder->grand_total)->toBe(1998.0);
});
