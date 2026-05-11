<?php

use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\CustomerInvoiceItem;
use App\Models\FiscalYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('customer-invoices');

test('customer invoice has expected relationships', function () {
    $invoice = CustomerInvoice::factory()->create();
    $item = CustomerInvoiceItem::factory()->create(['customer_invoice_id' => $invoice->id]);

    expect($invoice->customer)->toBeInstanceOf(Customer::class)
        ->and($invoice->branch)->toBeInstanceOf(Branch::class)
        ->and($invoice->fiscalYear)->toBeInstanceOf(FiscalYear::class)
        ->and($invoice->creator)->toBeInstanceOf(User::class)
        ->and($invoice->items->first()?->id)->toBe($item->id);
});
