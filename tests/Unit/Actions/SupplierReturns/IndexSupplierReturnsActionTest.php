<?php

use App\Actions\SupplierReturns\IndexSupplierReturnsAction;
use App\Domain\SupplierReturns\SupplierReturnFilterService;
use App\Http\Requests\SupplierReturns\IndexSupplierReturnRequest;
use App\Models\SupplierReturn;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('supplier-returns');

test('index action returns paginator', function () {
    SupplierReturn::factory()->count(5)->create();

    $request = new IndexSupplierReturnRequest();
    $request->merge([
        'per_page' => 10,
        'page' => 1,
    ]);

    $action = new IndexSupplierReturnsAction(new SupplierReturnFilterService());
    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
});
