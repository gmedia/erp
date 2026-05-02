<?php

use App\Models\ArReceipt;
use App\Models\ArReceiptAllocation;
use App\Models\Customer;
use App\Models\FiscalYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('ar-receipts');

test('ar receipt has expected relationships', function () {
    $receipt = ArReceipt::factory()->create();
    $allocation = ArReceiptAllocation::factory()->create(['ar_receipt_id' => $receipt->id]);

    expect($receipt->customer)->toBeInstanceOf(Customer::class)
        ->and($receipt->fiscalYear)->toBeInstanceOf(FiscalYear::class)
        ->and($receipt->creator)->toBeInstanceOf(User::class)
        ->and($receipt->allocations->first()?->id)->toBe($allocation->id);
});
