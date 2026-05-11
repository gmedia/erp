<?php

use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\FiscalYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('credit-notes');

test('credit note has expected relationships', function () {
    $invoice = CustomerInvoice::factory()->create();
    $creditNote = CreditNote::factory()->create(['customer_invoice_id' => $invoice->id]);
    $item = CreditNoteItem::factory()->create(['credit_note_id' => $creditNote->id]);

    expect($creditNote->customer)->toBeInstanceOf(Customer::class)
        ->and($creditNote->fiscalYear)->toBeInstanceOf(FiscalYear::class)
        ->and($creditNote->customerInvoice)->toBeInstanceOf(CustomerInvoice::class)
        ->and($creditNote->creator)->toBeInstanceOf(User::class)
        ->and($creditNote->items->first()?->id)->toBe($item->id);
});
