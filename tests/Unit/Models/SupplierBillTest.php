<?php

use App\Models\Branch;
use App\Models\FiscalYear;
use App\Models\Supplier;
use App\Models\SupplierBill;
use App\Models\SupplierBillItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('supplier-bills');

test('supplier bill has expected relationships', function () {
    $supplierBill = SupplierBill::factory()->create();
    $item = SupplierBillItem::factory()->create(['supplier_bill_id' => $supplierBill->id]);

    expect($supplierBill->supplier)->toBeInstanceOf(Supplier::class)
        ->and($supplierBill->branch)->toBeInstanceOf(Branch::class)
        ->and($supplierBill->fiscalYear)->toBeInstanceOf(FiscalYear::class)
        ->and($supplierBill->creator)->toBeInstanceOf(User::class)
        ->and($supplierBill->items->first()?->id)->toBe($item->id);
});