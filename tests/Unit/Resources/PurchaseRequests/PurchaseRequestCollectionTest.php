<?php

use App\Http\Resources\PurchaseRequests\PurchaseRequestCollection;
use App\Models\PurchaseRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('purchase-requests');

test('purchase request collection wraps resources', function () {
    $rows = PurchaseRequest::factory()->count(2)->create();
    $rows->load(['branch', 'department', 'requester', 'approver', 'creator', 'items.product', 'items.unit']);

    $collection = new PurchaseRequestCollection($rows);
    $data = $collection->toArray(new Request());

    expect($data)->toHaveCount(2)
        ->and($data[0])->toHaveKey('id');
});
