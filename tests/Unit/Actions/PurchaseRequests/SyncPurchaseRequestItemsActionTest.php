<?php

use App\Actions\PurchaseRequests\SyncPurchaseRequestItemsAction;
use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('purchase-requests');

test('sync items action recreates items and updates estimated amount', function () {
    $purchaseRequest = PurchaseRequest::factory()->create();
    $product = Product::factory()->create();
    $unit = Unit::factory()->create();

    $action = new SyncPurchaseRequestItemsAction;
    $action->execute($purchaseRequest, [
        [
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'quantity' => 2,
            'estimated_unit_price' => 1000,
            'notes' => 'item A',
        ],
    ]);

    $purchaseRequest->refresh();

    expect($purchaseRequest->items)->toHaveCount(1)
        ->and((float) $purchaseRequest->estimated_amount)->toBe(2000.0);
});
