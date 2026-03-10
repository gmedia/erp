<?php

use App\Actions\PurchaseOrders\IndexPurchaseOrdersAction;
use App\Domain\PurchaseOrders\PurchaseOrderFilterService;
use App\Http\Requests\PurchaseOrders\IndexPurchaseOrderRequest;
use App\Models\PurchaseOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('purchase-orders');

test('index action returns paginator', function () {
    PurchaseOrder::factory()->count(5)->create();

    $request = new IndexPurchaseOrderRequest;
    $request->merge([
        'per_page' => 10,
        'page' => 1,
    ]);

    $action = new IndexPurchaseOrdersAction(new PurchaseOrderFilterService);
    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
});
