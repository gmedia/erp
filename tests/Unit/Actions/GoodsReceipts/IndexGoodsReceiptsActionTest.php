<?php

use App\Actions\GoodsReceipts\IndexGoodsReceiptsAction;
use App\Domain\GoodsReceipts\GoodsReceiptFilterService;
use App\Http\Requests\GoodsReceipts\IndexGoodsReceiptRequest;
use App\Models\GoodsReceipt;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('goods-receipts');

test('index action returns paginator', function () {
    GoodsReceipt::factory()->count(5)->create();

    $request = new IndexGoodsReceiptRequest;
    $request->merge([
        'per_page' => 10,
        'page' => 1,
    ]);

    $action = new IndexGoodsReceiptsAction(new GoodsReceiptFilterService);
    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
});
