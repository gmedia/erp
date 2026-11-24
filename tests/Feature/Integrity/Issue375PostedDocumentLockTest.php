<?php

use App\Models\ApPayment;
use App\Models\ArReceipt;
use App\Models\CustomerInvoice;
use App\Models\JournalEntry;
use App\Models\SupplierBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('issue-375');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'customer_invoice', 'customer_invoice.edit',
        'supplier_bill', 'supplier_bill.edit',
        'ar_receipt', 'ar_receipt.edit',
        'ap_payment', 'ap_payment.edit',
    ]);
    Sanctum::actingAs($user, ['*']);
});

test('item invoice terposting yang berubah ditolak 422 dengan pesan terkunci', function () {
    $invoice = CustomerInvoice::factory()->create([
        'status' => 'sent',
        'journal_entry_id' => JournalEntry::factory()->create()->id,
        'grand_total' => 100000,
    ]);

    putJson("/api/customer-invoices/{$invoice->id}", [
        'status' => 'sent',
        'grand_total' => 999999,
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['grand_total']);
});

test('dokumen terposting dengan nilai identik tetap diterima', function () {
    $invoice = CustomerInvoice::factory()->create([
        'status' => 'sent',
        'journal_entry_id' => JournalEntry::factory()->create()->id,
        'grand_total' => 100000,
    ]);

    putJson("/api/customer-invoices/{$invoice->id}", [
        'status' => 'sent',
        'grand_total' => 100000,
    ])->assertOk();
});

test('item supplier bill terposting yang berubah ditolak 422', function () {
    $bill = SupplierBill::factory()->create([
        'status' => 'confirmed',
        'journal_entry_id' => JournalEntry::factory()->create()->id,
        'grand_total' => 50000,
    ]);

    putJson("/api/supplier-bills/{$bill->id}", [
        'status' => 'confirmed',
        'grand_total' => 75000,
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['grand_total']);
});

test('dokumen draft tanpa jurnal tetap boleh mengubah nilai', function () {
    $invoice = CustomerInvoice::factory()->create([
        'status' => 'draft',
        'journal_entry_id' => null,
        'grand_total' => 100000,
    ]);

    putJson("/api/customer-invoices/{$invoice->id}", [
        'status' => 'draft',
        'grand_total' => 120000,
    ])->assertOk();
});

test('total ar receipt terposting yang berubah ditolak 422', function () {
    $receipt = ArReceipt::factory()->create([
        'status' => 'confirmed',
        'journal_entry_id' => JournalEntry::factory()->create()->id,
        'total_amount' => 100000,
    ]);

    putJson("/api/ar-receipts/{$receipt->id}", [
        'status' => 'confirmed',
        'total_amount' => 999999,
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['total_amount']);
});

test('total ap payment terposting yang berubah ditolak 422', function () {
    $payment = ApPayment::factory()->create([
        'status' => 'confirmed',
        'journal_entry_id' => JournalEntry::factory()->create()->id,
        'total_amount' => 100000,
    ]);

    putJson("/api/ap-payments/{$payment->id}", [
        'status' => 'confirmed',
        'total_amount' => 999999,
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['total_amount']);
});
