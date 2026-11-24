<?php

use App\Models\Account;
use App\Models\ArReceipt;
use App\Models\CoaVersion;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\SupplierBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('issue-375');

/** Akun postable pada COA aktif yang mencakup tanggal hari ini. */
function postableAccount(): Account
{
    $coa = CoaVersion::query()->where('status', 'active')->first()
        ?? CoaVersion::factory()->create(['status' => 'active']);

    return Account::factory()->create([
        'coa_version_id' => $coa->id,
        'is_active' => true,
    ]);
}

/** Jurnal posted seimbang dengan dua baris, syarat ReverseDocumentJournalAction. */
function postedJournalWithLine(): JournalEntry
{
    $entry = JournalEntry::factory()->create(['status' => 'posted']);
    $entry->lines()->create([
        'account_id' => postableAccount()->id,
        'debit' => 100000,
        'credit' => 0,
    ]);
    $entry->lines()->create([
        'account_id' => postableAccount()->id,
        'debit' => 0,
        'credit' => 100000,
    ]);

    return $entry;
}

beforeEach(function () {
    FiscalYear::factory()->create([
        'name' => (string) now()->year,
        'start_date' => now()->startOfYear()->toDateString(),
        'end_date' => now()->endOfYear()->toDateString(),
        'status' => 'open',
    ]);

    $user = createTestUserWithPermissions([
        'customer_invoice', 'customer_invoice.edit',
        'supplier_bill', 'supplier_bill.edit',
        'ar_receipt', 'ar_receipt.edit',
        'ap_payment', 'ap_payment.edit',
    ]);
    Sanctum::actingAs($user, ['*']);
});

/*
 * Review PR #426, butir 1: void pada invoice terposting harus membalik jurnal.
 */
test('invoice terposting yang di-void membalik jurnal asli dan status menjadi void', function () {
    $entry = postedJournalWithLine();
    $invoice = CustomerInvoice::factory()->create([
        'status' => 'sent',
        'invoice_number' => 'INV-2026-000427',
        'journal_entry_id' => $entry->id,
        'grand_total' => 100000,
    ]);

    putJson("/api/customer-invoices/{$invoice->id}", ['status' => 'void'])->assertOk();

    expect($invoice->fresh()->status)->toBe('void')
        ->and(JournalEntry::query()
            ->where('source_type', CustomerInvoice::class)
            ->where('source_id', $invoice->id)
            ->where('id', '!=', $entry->id)
            ->exists())->toBeTrue();
});

/*
 * Review PR #426, butir 2: koleksi terkunci per baris, bukan hanya total.
 * Akun baris diganti, total sama: harus ditolak.
 */
test('item invoice terposting dengan akun diganti tetapi total sama ditolak', function () {
    $accountA = postableAccount();
    $accountB = postableAccount();
    $invoice = CustomerInvoice::factory()->create([
        'status' => 'sent',
        'journal_entry_id' => JournalEntry::factory()->create()->id,
        'grand_total' => 100000,
    ]);
    $invoice->items()->create([
        'account_id' => $accountA->id,
        'description' => 'Baris asli',
        'quantity' => 1,
        'unit_price' => 100000,
        'line_total' => 100000,
    ]);

    putJson("/api/customer-invoices/{$invoice->id}", [
        'status' => 'sent',
        'items' => [[
            'account_id' => $accountB->id,
            'description' => 'Baris asli',
            'quantity' => 1,
            'unit_price' => 100000,
        ]],
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['items']);
});

test('items kosong pada dokumen terposting ditolak 422', function () {
    $invoice = CustomerInvoice::factory()->create([
        'status' => 'sent',
        'journal_entry_id' => JournalEntry::factory()->create()->id,
        'grand_total' => 100000,
    ]);
    $invoice->items()->create([
        'account_id' => postableAccount()->id,
        'description' => 'Baris asli',
        'quantity' => 1,
        'unit_price' => 100000,
        'line_total' => 100000,
    ]);

    putJson("/api/customer-invoices/{$invoice->id}", [
        'status' => 'sent',
        'items' => [],
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['items']);
});

/*
 * Review PR #426, butir 3: header yang ikut posting terkunci.
 * Nilai sama tetap diterima (lihat Issue375PostedDocumentLockTest).
 */
test('customer_id dokumen terposting tidak boleh diganti', function () {
    $invoice = CustomerInvoice::factory()->create([
        'status' => 'sent',
        'journal_entry_id' => JournalEntry::factory()->create()->id,
        'grand_total' => 100000,
    ]);
    $otherCustomerId = Customer::factory()->create()->id;

    putJson("/api/customer-invoices/{$invoice->id}", [
        'status' => 'sent',
        'customer_id' => $otherCustomerId,
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['customer_id']);
});

test('tanggal dokumen terposting tidak boleh diganti', function () {
    $bill = SupplierBill::factory()->create([
        'status' => 'confirmed',
        'journal_entry_id' => JournalEntry::factory()->create()->id,
        'grand_total' => 50000,
        'bill_date' => '2026-01-10',
    ]);

    putJson("/api/supplier-bills/{$bill->id}", [
        'status' => 'confirmed',
        'bill_date' => '2026-02-10',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['bill_date']);
});

/*
 * Review PR #426, butir 4: pembatalan hanya boleh mengubah status dan notes.
 * Field lain di payload PUT diabaikan, tidak ikut ter-update.
 */
test('pembatalan invoice terposting tidak menulis field selain status dan notes', function () {
    $invoice = CustomerInvoice::factory()->create([
        'status' => 'sent',
        'invoice_number' => 'INV-2026-000426',
        'journal_entry_id' => postedJournalWithLine()->id,
        'notes' => 'asli',
        'invoice_date' => '2026-01-10',
    ]);

    putJson("/api/customer-invoices/{$invoice->id}", [
        'status' => 'cancelled',
        'notes' => 'dibatalkan',
    ])->assertOk();

    $fresh = $invoice->fresh();

    expect($fresh->status)->toBe('cancelled')
        ->and($fresh->notes)->toBe('dibatalkan')
        ->and($fresh->invoice_date->toDateString())->toBe('2026-01-10');

});

test('pembatalan ar receipt terposting tidak menulis total_amount dari payload', function () {
    $receipt = ArReceipt::factory()->create([
        'status' => 'confirmed',
        'receipt_number' => 'RCP-2026-000426',
        'journal_entry_id' => postedJournalWithLine()->id,
        'total_amount' => 100000,
    ]);

    putJson("/api/ar-receipts/{$receipt->id}", [
        'status' => 'cancelled',
        'total_amount' => 100000,
        'notes' => 'batal',
    ])->assertOk();

    expect($receipt->fresh())
        ->status->toBe('cancelled')
        ->total_amount->toEqual('100000.00');
});
