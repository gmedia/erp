<?php

use App\Domain\GoodsReceipts\GoodsReceiptFilterService;
use App\Models\GoodsReceipt;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('goods-receipts');

test('filter service applies warehouse and status filters', function () {
    $warehouse = Warehouse::factory()->create();
    GoodsReceipt::factory()->create(['warehouse_id' => $warehouse->id, 'status' => 'draft']);
    GoodsReceipt::factory()->create(['status' => 'confirmed']);

    $query = GoodsReceipt::query();
    $service = new GoodsReceiptFilterService();
    $service->applyAdvancedFilters($query, [
        'warehouse_id' => $warehouse->id,
        'status' => 'draft',
    ]);

    expect($query->count())->toBe(1);
});
