<?php

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('purchase-requests');

test('purchase request has expected relationships', function () {
    $purchaseRequest = PurchaseRequest::factory()->create();
    $item = PurchaseRequestItem::factory()->create(['purchase_request_id' => $purchaseRequest->id]);

    expect($purchaseRequest->branch)->toBeInstanceOf(Branch::class)
        ->and($purchaseRequest->department)->toBeInstanceOf(Department::class)
        ->and($purchaseRequest->requester)->toBeInstanceOf(Employee::class)
        ->and($purchaseRequest->creator)->toBeInstanceOf(User::class)
        ->and($purchaseRequest->items->first()?->id)->toBe($item->id);
});
