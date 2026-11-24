<?php

use App\Actions\PurchaseRequests\SyncPurchaseRequestItemsAction;
use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('rounding-line-amounts');

test('purchase request estimated amount equals sum of rounded line estimates', function () {
    $purchaseRequest = PurchaseRequest::factory()->create();
    $unit = Unit::factory()->create();

    // Estimasi per baris = 3 x 1.005 = 3.015 -> round(3.015, 2) = 3.01 (float 3.015 sedikit di bawah),
    // total 3 baris = 9.03. Pembulatan total mentah 9.045 -> 9.05 akan menyimpang dari jumlah baris.
    $items = [];
    for ($i = 0; $i < 3; $i++) {
        $items[] = [
            'product_id' => Product::factory()->create()->id,
            'unit_id' => $unit->id,
            'quantity' => 3,
            'estimated_unit_price' => 1.005,
        ];
    }

    $action = new SyncPurchaseRequestItemsAction;
    $action->execute($purchaseRequest, $items);

    $purchaseRequest->refresh();

    $lineSum = round($purchaseRequest->items->sum(fn ($row) => round((float) $row->estimated_total, 2)), 2);

    expect(round((float) $purchaseRequest->estimated_amount, 2))->toBe($lineSum)
        ->and($lineSum)->toBe(9.03);
});
