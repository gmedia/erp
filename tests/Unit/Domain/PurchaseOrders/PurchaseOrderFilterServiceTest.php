<?php

use App\Domain\PurchaseOrders\PurchaseOrderFilterService;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('purchase-orders');

test('filter service applies supplier and status filters', function () {
    $supplier = Supplier::factory()->create();
    PurchaseOrder::factory()->create(['supplier_id' => $supplier->id, 'status' => 'draft']);
    PurchaseOrder::factory()->create(['status' => 'confirmed']);

    $query = PurchaseOrder::query();
    $service = new PurchaseOrderFilterService();
    $service->applyAdvancedFilters($query, [
        'supplier_id' => $supplier->id,
        'status' => 'draft',
    ]);

    expect($query->count())->toBe(1);
});
