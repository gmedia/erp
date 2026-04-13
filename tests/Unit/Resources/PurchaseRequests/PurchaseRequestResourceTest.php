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
    $product = Product::factory()->create(['name' => 'Stapler']);
    $unit = Unit::factory()->create(['name' => 'Unit']);
    $purchaseRequest->items()->create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'quantity' => 4,
        'estimated_unit_price' => 120000,
        'estimated_total' => 480000,
        'notes' => 'Need before Friday',
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

    expect($data['items'][0])->toMatchArray([
        'product' => [
            'id' => $product->id,
            'name' => 'Stapler',
        ],
        'unit' => [
            'id' => $unit->id,
            'name' => 'Unit',
        ],
        'quantity' => '4.00',
        'estimated_unit_price' => '120000.00',
        'estimated_total' => '480000.00',
        'notes' => 'Need before Friday',
    ]);
});
