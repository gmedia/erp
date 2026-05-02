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
    $creditNote = CreditNote::factory()->create();
    $item = CreditNoteItem::factory()->create(['credit_note_id' => $creditNote->id]);

    expect($creditNote->customer)->toBeInstanceOf(Customer::class)
        ->and($creditNote->fiscalYear)->toBeInstanceOf(FiscalYear::class)
        ->and($creditNote->customerInvoice)->toBeInstanceOf(CustomerInvoice::class)
        ->and($creditNote->creator)->toBeInstanceOf(User::class)
        ->and($creditNote->items->first()?->id)->toBe($item->id);
});
