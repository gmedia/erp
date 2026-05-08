<?php

use App\Models\Account;
use App\Models\ApPayment;
use App\Models\ApPaymentAllocation;
use App\Models\Branch;
use App\Models\FiscalYear;
use App\Models\Supplier;
use App\Models\SupplierBill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('ap-payments');

test('ap payment has expected relationships', function () {
    $apPayment = ApPayment::factory()->create();
    $supplierBill = SupplierBill::factory()->confirmed()->create();
    $allocation = ApPaymentAllocation::factory()->create([
        'ap_payment_id' => $apPayment->id,
        'supplier_bill_id' => $supplierBill->id,
    ]);

    expect($apPayment->supplier)->toBeInstanceOf(Supplier::class)
        ->and($apPayment->branch)->toBeInstanceOf(Branch::class)
        ->and($apPayment->fiscalYear)->toBeInstanceOf(FiscalYear::class)
        ->and($apPayment->bankAccount)->toBeInstanceOf(Account::class)
        ->and($apPayment->creator)->toBeInstanceOf(User::class)
        ->and($apPayment->allocations->first()?->id)->toBe($allocation->id);
});
