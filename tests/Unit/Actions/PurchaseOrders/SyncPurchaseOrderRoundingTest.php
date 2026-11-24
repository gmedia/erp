<?php

use App\Actions\PurchaseOrders\SyncPurchaseOrderItemsAction;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('rounding-line-amounts');

test('purchase order header equals sum of rounded line totals for fractional lines', function () {
    $purchaseOrder = PurchaseOrder::factory()->create();
    $product = Product::factory()->create();
    $unit = Unit::factory()->create();

    // Tiga baris pecahan: masing-masing line_total berakhiran .xx5 sehingga pembulatan per baris berbeda
    // dengan pembulatan total mentah.
    $items = [];
    foreach ([[3, 0.335, 0, 11], [3, 0.335, 0, 11], [3, 0.335, 0, 11]] as [$qty, $price, $disc, $tax]) {
        $items[] = [
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'quantity' => $qty,
            'unit_price' => $price,
            'discount_percent' => $disc,
            'tax_percent' => $tax,
        ];
    }

    $action = new SyncPurchaseOrderItemsAction;
    $action->execute($purchaseOrder, $items);

    $purchaseOrder->refresh();

    $lineSum = round(
        $purchaseOrder->items->sum(fn ($row) => round((float) $row->line_total, 2)),
        2,
    );

    expect(round((float) $purchaseOrder->grand_total, 2))->toBe($lineSum);
});
