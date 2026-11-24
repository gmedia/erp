<?php

use App\Models\Account;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\FiscalYear;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierBill;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('backend-validation-messages');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'ap_payment',
        'ap_payment.create',
        'ar_receipt',
        'ar_receipt.create',
        'goods_receipt',
        'goods_receipt.create',
        'supplier_return',
        'supplier_return.create',
    ]);
    Sanctum::actingAs($user, ['*']);
});

/**
 * Pesan validasi backend harus berupa teks terjemahan (bukan kunci mentah),
 * dan harus sama dengan isi lang/{locale}.json untuk locale aktif.
 */
function assertValidationMessageTranslated(
    TestResponse $response,
    string $field,
    string $key,
    array $replace = [],
): void {
    // Key error berisi titik (mis. "allocations.0.supplier_bill_id"), jadi jangan lewat dot-path json().
    $messages = $response->json('errors')[$field] ?? null;

    expect($messages)->toBeArray()->not->toBeEmpty();
    expect($messages[0])
        ->toBe(__($key, $replace))
        ->not->toBe($key);
}

test('ap payment rejects allocation to a bill of another supplier with translated message', function () {
    $supplier = Supplier::factory()->create();
    $otherSupplier = Supplier::factory()->create();
    $bankAccount = Account::factory()->create(['type' => 'asset']);
    $foreignBill = SupplierBill::factory()->confirmed()->create(['supplier_id' => $otherSupplier->id]);

    $response = postJson('/api/ap-payments', [
        'supplier_id' => $supplier->id,
        'branch_id' => Branch::factory()->create()->id,
        'fiscal_year_id' => FiscalYear::factory()->create()->id,
        'payment_date' => '2026-03-05',
        'payment_method' => 'bank_transfer',
        'bank_account_id' => $bankAccount->id,
        'currency' => 'IDR',
        'total_amount' => 1000,
        'status' => 'draft',
        'allocations' => [
            ['supplier_bill_id' => $foreignBill->id, 'allocated_amount' => 1000],
        ],
    ]);

    $response->assertUnprocessable();
    assertValidationMessageTranslated($response, 'allocations.0.supplier_bill_id', 'ap_payment.bill_supplier_mismatch');
});

test('backend validation message keys exist flat in both en and id', function () {
    foreach (['en', 'id'] as $locale) {
        $strings = json_decode(file_get_contents(lang_path("{$locale}.json")), true);

        foreach ([
            'ap_payment.bill_supplier_mismatch',
            'ap_payment.allocation_exceeds_bill',
            'ar_receipt.allocation_exceeds_invoice',
            'ar_receipt.invoice_customer_mismatch',
            'ar_receipt.invoice_status_not_allocatable',
            'goods_receipt.po_item_mismatch',
            'supplier_return.supplier_mismatch',
            'supplier_return.goods_receipt_mismatch',
            'supplier_return.gr_item_mismatch',
        ] as $key) {
            expect($strings)->toHaveKey($key);
            expect($strings[$key])->not->toBe($key);
        }
    }

    app()->setLocale('id');
    expect(__('ap_payment.bill_supplier_mismatch'))->toBe('Supplier Bill tidak milik supplier yang dipilih.');
    expect(__('ar_receipt.invoice_status_not_allocatable', ['status' => 'void']))
        ->toBe('Tidak bisa mengalokasikan ke invoice berstatus void.');
    app()->setLocale('en');
});

test('ar receipt rejects allocation to an invoice of another customer with translated message', function () {
    $invoice = CustomerInvoice::factory()->create();

    $response = postJson('/api/ar-receipts', [
        'customer_id' => Customer::factory()->create()->id,
        'allocations' => [
            ['customer_invoice_id' => $invoice->id, 'allocated_amount' => 1],
        ],
    ]);

    $response->assertUnprocessable();
    assertValidationMessageTranslated(
        $response,
        'allocations.0.customer_invoice_id',
        'ar_receipt.invoice_customer_mismatch',
    );
});

test('supplier return rejects mismatched supplier with translated message', function () {
    $response = postJson('/api/supplier-returns', [
        'purchase_order_id' => PurchaseOrder::factory()->create()->id,
        'supplier_id' => Supplier::factory()->create()->id,
        'warehouse_id' => Warehouse::factory()->create()->id,
        'return_date' => now()->format('Y-m-d'),
        'reason' => 'defective',
        'status' => 'draft',
        'items' => [],
    ]);

    $response->assertUnprocessable();
    assertValidationMessageTranslated($response, 'supplier_id', 'supplier_return.supplier_mismatch');
});
