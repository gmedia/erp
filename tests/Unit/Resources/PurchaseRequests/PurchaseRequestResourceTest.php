<?php

use App\Http\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('purchase-requests');

test('purchase request resource returns expected structure', function () {
    $purchaseRequest = PurchaseRequest::factory()->create();
    $purchaseRequest->items()->create([
        'product_id' => Product::factory()->create()->id,
        'unit_id' => Unit::factory()->create()->id,
        'quantity' => 4,
    ]);
    $purchaseRequest->load(['branch', 'department', 'requester', 'approver', 'creator', 'items.product', 'items.unit']);

    $data = (new PurchaseRequestResource($purchaseRequest))->toArray(new Request);

    expect($data)->toHaveKeys([
        'id',
        'pr_number',
        'branch',
        'department',
        'requester',
        'request_date',
        'priority',
        'status',
        'items',
    ]);
});
