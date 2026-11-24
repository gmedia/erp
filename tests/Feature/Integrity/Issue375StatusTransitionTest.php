<?php

use App\Models\ApPayment;
use App\Models\ArReceipt;
use App\Models\CreditNote;
use App\Models\CustomerInvoice;
use App\Models\GoodsReceipt;
use App\Models\SupplierBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('issue-375');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'goods_receipt', 'goods_receipt.edit',
        'customer_invoice', 'customer_invoice.edit',
        'supplier_bill', 'supplier_bill.edit',
        'ap_payment', 'ap_payment.edit',
        'ar_receipt', 'ar_receipt.edit',
        'credit_note', 'credit_note.edit',
    ]);
    Sanctum::actingAs($user, ['*']);
});

/**
 * Dataset: [modul, factory-model, endpoint, asal, tujuan, HTTP yang diharapkan].
 * Status sama dengan tersimpan selalu 200 (kirim ulang aman).
 */
$transitions = [
    'goods-receipts' => [GoodsReceipt::class, [
        'gr draft to confirmed is allowed' => ['draft', 'confirmed', 200],
        'gr confirmed back to draft is rejected' => ['confirmed', 'draft', 422],
        'gr cancelled to confirmed is rejected' => ['cancelled', 'confirmed', 422],
        'gr cancelled to draft is rejected' => ['cancelled', 'draft', 422],
        'gr confirmed resent unchanged is allowed' => ['confirmed', 'confirmed', 200],
        'gr cancelled resent unchanged is allowed' => ['cancelled', 'cancelled', 200],
    ]],
    'customer-invoices' => [CustomerInvoice::class, [
        'invoice draft to sent is allowed' => ['draft', 'sent', 200],
        'invoice sent back to draft is rejected' => ['sent', 'draft', 422],
        'invoice cancelled to sent is rejected' => ['cancelled', 'sent', 422],
        'invoice void to draft is rejected' => ['void', 'draft', 422],
        'invoice sent to cancelled is allowed' => ['sent', 'cancelled', 200],
        'invoice cancelled resent unchanged is allowed' => ['cancelled', 'cancelled', 200],
    ]],
    'supplier-bills' => [SupplierBill::class, [
        'bill draft to cancelled is allowed' => ['draft', 'cancelled', 200],
        'bill confirmed back to draft is rejected' => ['confirmed', 'draft', 422],
        'bill cancelled to confirmed is rejected' => ['cancelled', 'confirmed', 422],
        'bill confirmed to cancelled is allowed' => ['confirmed', 'cancelled', 200],
        'bill cancelled resent unchanged is allowed' => ['cancelled', 'cancelled', 200],
    ]],
    'ap-payments' => [ApPayment::class, [
        'ap payment confirmed to reconciled is allowed' => ['confirmed', 'reconciled', 200],
        'ap payment reconciled back to draft is rejected' => ['reconciled', 'draft', 422],
        'ap payment void to confirmed is rejected' => ['void', 'confirmed', 422],
        'ap payment draft to cancelled is allowed' => ['draft', 'cancelled', 200],
        'ap payment cancelled resent unchanged is allowed' => ['cancelled', 'cancelled', 200],
    ]],
    'ar-receipts' => [ArReceipt::class, [
        'ar receipt confirmed to reconciled is allowed' => ['confirmed', 'reconciled', 200],
        'ar receipt reconciled back to draft is rejected' => ['reconciled', 'draft', 422],
        'ar receipt cancelled to confirmed is rejected' => ['cancelled', 'confirmed', 422],
        'ar receipt draft to void is allowed' => ['draft', 'void', 200],
        'ar receipt void resent unchanged is allowed' => ['void', 'void', 200],
    ]],
    'credit-notes' => [CreditNote::class, [
        'credit note draft to void is allowed' => ['draft', 'void', 200],
        'credit note applied back to confirmed is rejected' => ['applied', 'confirmed', 422],
        'credit note void to draft is rejected' => ['void', 'draft', 422],
        'credit note applied resent unchanged is allowed' => ['applied', 'applied', 200],
    ]],
];

/** Dataset: [endpoint, model, asal, tujuan, HTTP yang diharapkan] per modul. */
dataset('status transitions', function () use ($transitions) {
    foreach ($transitions as $endpoint => [$model, $cases]) {
        foreach ($cases as $name => [$from, $to, $expected]) {
            yield $name => [$endpoint, $model, $from, $to, $expected];
        }
    }
});

test('status transition per dokumen follows the table', function (
    string $endpoint,
    string $modelClass,
    string $from,
    string $to,
    int $expected,
) {
    $record = $modelClass::factory()->create(['status' => $from]);

    putJson("/api/{$endpoint}/{$record->id}", ['status' => $to])->assertStatus($expected);

    if ($expected === 422) {
        expect($record->refresh()->status)->toBe($from);
    }
})->with('status transitions');
