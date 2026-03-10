<?php

use App\Domain\PurchaseRequests\PurchaseRequestFilterService;
use App\Models\Branch;
use App\Models\PurchaseRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('purchase-requests');

test('filter service applies branch and status filters', function () {
    $branch = Branch::factory()->create();
    PurchaseRequest::factory()->create(['branch_id' => $branch->id, 'status' => 'draft']);
    PurchaseRequest::factory()->create(['status' => 'approved']);

    $query = PurchaseRequest::query();
    $service = new PurchaseRequestFilterService;
    $service->applyAdvancedFilters($query, [
        'branch_id' => $branch->id,
        'status' => 'draft',
    ]);

    expect($query->count())->toBe(1);
});
