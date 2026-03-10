<?php

use App\Actions\PurchaseRequests\IndexPurchaseRequestsAction;
use App\Domain\PurchaseRequests\PurchaseRequestFilterService;
use App\Http\Requests\PurchaseRequests\IndexPurchaseRequestRequest;
use App\Models\PurchaseRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('purchase-requests');

test('index action returns paginator', function () {
    PurchaseRequest::factory()->count(5)->create();

    $request = new IndexPurchaseRequestRequest;
    $request->merge([
        'per_page' => 10,
        'page' => 1,
    ]);

    $action = new IndexPurchaseRequestsAction(new PurchaseRequestFilterService);
    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
});
