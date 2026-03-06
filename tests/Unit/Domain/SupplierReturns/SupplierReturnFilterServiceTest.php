<?php

use App\Domain\SupplierReturns\SupplierReturnFilterService;
use App\Models\Supplier;
use App\Models\SupplierReturn;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('supplier-returns');

test('filter service applies supplier and status filters', function () {
    $supplier = Supplier::factory()->create();
    SupplierReturn::factory()->create(['supplier_id' => $supplier->id, 'status' => 'draft']);
    SupplierReturn::factory()->create(['status' => 'confirmed']);

    $query = SupplierReturn::query();
    $service = new SupplierReturnFilterService();
    $service->applyAdvancedFilters($query, [
        'supplier_id' => $supplier->id,
        'status' => 'draft',
    ]);

    expect($query->count())->toBe(1);
});
