<?php

use App\Models\Account;
use App\Models\ApPayment;
use App\Models\ApPaymentAllocation;
use App\Models\ArReceipt;
use App\Models\ArReceiptAllocation;
use App\Models\Branch;
use App\Models\CoaVersion;
use App\Models\CustomerInvoice;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\SupplierBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('issue-437');

/**
 * Issue #437 (keputusan product owner, 2026-11-24): diskon alokasi AR/AP dijurnal ke akun diskon,
 * mengurangi sisa dokumen, dan batal mengembalikan semuanya. Tanpa akun di COA: 422.
 */
beforeEach(function () {
    Sanctum::actingAs(createTestUserWithPermissions([
        'ar_receipt', 'ar_receipt.edit', 'ar_receipt.create',
        'ap_payment', 'ap_payment.edit', 'ap_payment.create',
    ]), ['*']);

    FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);
});

function issue437Coa(bool $withDiscountAccounts = true): CoaVersion
{
    $coa = CoaVersion::factory()->create(['status' => 'active']);

    $make = fn (string $code, string $name, string $type, string $normal) => Account::factory()->create([
        'coa_version_id' => $coa->id,
        'code' => $code,
        'name' => $name,
        'type' => $type,
        'normal_balance' => $normal,
        'is_active' => true,
    ]);

    $make('11200', 'Accounts Receivable', 'asset', 'debit');
    $make('21100', 'Accounts Payable', 'liability', 'credit');
    $make('11110', 'Cash in Bank', 'asset', 'debit');
    $make('41000', 'Sales Revenue', 'revenue', 'credit');
    $make('51000', 'Cost of Goods Sold', 'expense', 'debit');

    if ($withDiscountAccounts) {
        $make('41900', 'Sales Discount', 'revenue', 'debit');
        $make('51900', 'Purchase Discount', 'expense', 'credit');
    }

    return $coa;
}

function issue437Branch(): int
{
    return Branch::factory()->create()->id;
}

function issue437Bank(): int
{
    return Account::query()->where('code', '11110')->value('id');
}

function issue437Invoice(float $grand = 100000): CustomerInvoice
{
    return CustomerInvoice::factory()->sent()->create([
        'invoice_date' => '2026-03-01',
        'grand_total' => $grand,
        'amount_received' => 0,
        'credit_note_amount' => 0,
        'amount_due' => $grand,
    ]);
}

function issue437Bill(float $grand = 100000): SupplierBill
{
    return SupplierBill::factory()->create([
        'status' => 'confirmed',
        'bill_date' => '2026-03-01',
        'grand_total' => $grand,
        'amount_paid' => 0,
        'amount_due' => $grand,
    ]);
}

test('diskon alokasi AR dijurnal ke akun diskon dan mengurangi sisa invoice sampai lunas', function () {
    issue437Coa();
    $invoice = issue437Invoice(100000);

    $receipt = ArReceipt::factory()->create([
        'status' => 'draft',
        'confirmed_at' => null,
        'confirmed_by' => null,
        'customer_id' => $invoice->customer_id,
        'branch_id' => issue437Branch(),
        'receipt_number' => 'RCP-437-' . fake()->unique()->numerify('####'),
        'bank_account_id' => issue437Bank(),
        'receipt_date' => '2026-03-10',
        'total_amount' => 95000,
    ]);

    ArReceiptAllocation::query()->create([
        'ar_receipt_id' => $receipt->id,
        'customer_invoice_id' => $invoice->id,
        'allocated_amount' => 95000,
        'discount_given' => 5000,
    ]);

    putJson("/api/ar-receipts/{$receipt->id}", ['status' => 'confirmed'])->assertOk();

    $entry = JournalEntry::query()
        ->where('source_type', ArReceipt::class)
        ->where('source_id', $receipt->id)
        ->firstOrFail();

    $discountAccount = Account::query()->where('code', '41900')->first();
    $discountLine = $entry->lines()->where('account_id', $discountAccount->id)->first();
    expect($discountLine)->not->toBeNull()
        ->and((float) $discountLine->debit)->toBe(5000.0);

    expect((float) $entry->total_debit)->toBe((float) $entry->total_credit);

    $invoice->refresh();
    expect((float) $invoice->amount_due)->toBe(0.0)
        ->and($invoice->status)->toBe('paid');
});

test('membatalkan receipt dengan diskon mengembalikan sisa invoice dan membalik jurnal diskon', function () {
    issue437Coa();
    $invoice = issue437Invoice(100000);
    $receipt = ArReceipt::factory()->create([
        'status' => 'draft',
        'confirmed_at' => null,
        'confirmed_by' => null,
        'customer_id' => $invoice->customer_id,
        'branch_id' => issue437Branch(),
        'receipt_number' => 'RCP-437-' . fake()->unique()->numerify('####'),
        'bank_account_id' => issue437Bank(),
        'receipt_date' => '2026-03-10',
        'total_amount' => 95000,
    ]);
    ArReceiptAllocation::query()->create([
        'ar_receipt_id' => $receipt->id,
        'customer_invoice_id' => $invoice->id,
        'allocated_amount' => 95000,
        'discount_given' => 5000,
    ]);

    putJson("/api/ar-receipts/{$receipt->id}", ['status' => 'confirmed'])->assertOk();
    putJson("/api/ar-receipts/{$receipt->id}", ['status' => 'cancelled'])->assertOk();

    expect(ArReceiptAllocation::query()->where('ar_receipt_id', $receipt->id)->exists())->toBeFalse();

    $invoice->refresh();
    expect((float) $invoice->amount_due)->toBe(100000.0)
        ->and($invoice->status)->toBe('sent');

    $discountAccount = Account::query()->where('code', '41900')->first();
    $reversal = JournalEntry::query()
        ->where('source_type', ArReceipt::class)
        ->where('source_id', $receipt->id)
        ->where('description', 'like', 'Reversal%')
        ->firstOrFail();
    expect((float) $reversal->lines()->where('account_id', $discountAccount->id)->first()->credit)->toBe(5000.0);
});

test('batas alokasi AR menghitung diskon: alokasi plus diskon tidak boleh melebihi sisa', function () {
    issue437Coa();
    $invoice = issue437Invoice(100000);
    postJson('/api/ar-receipts', [
        'receipt_date' => '2026-03-10',
        'customer_id' => $invoice->customer_id,
        'branch_id' => issue437Branch(),
        'fiscal_year_id' => $invoice->fiscal_year_id,
        'currency' => 'IDR',
        'bank_account_id' => issue437Bank(),
        'payment_method' => 'bank_transfer',
        'status' => 'draft',
        'confirmed_at' => null,
        'confirmed_by' => null,
        'total_amount' => 60000,
        'allocations' => [[
            'customer_invoice_id' => $invoice->id,
            'allocated_amount' => 60000,
            'discount_given' => 50000,
        ]],
    ])->assertStatus(422)->assertJsonValidationErrors(['allocations.0.allocated_amount']);
});

test('diskon alokasi AP dijurnal ke akun diskon pembelian dan mengurangi sisa bill sampai lunas', function () {
    issue437Coa();
    $bill = issue437Bill(100000);

    $payment = ApPayment::factory()->create([
        'status' => 'draft',
        'confirmed_at' => null,
        'confirmed_by' => null,
        'supplier_id' => $bill->supplier_id,
        'branch_id' => issue437Branch(),
        'payment_number' => 'PAY-437-' . fake()->unique()->numerify('####'),
        'bank_account_id' => issue437Bank(),
        'payment_date' => '2026-03-10',
        'total_amount' => 95000,
    ]);
    ApPaymentAllocation::query()->create([
        'ap_payment_id' => $payment->id,
        'supplier_bill_id' => $bill->id,
        'allocated_amount' => 95000,
        'discount_taken' => 5000,
    ]);

    putJson("/api/ap-payments/{$payment->id}", ['status' => 'confirmed'])->assertOk();

    $entry = $payment->fresh()->journalEntry()->first();
    expect($entry)->not->toBeNull();

    $discountAccount = Account::query()->where('code', '51900')->first();
    $discountLine = $entry->lines()->where('account_id', $discountAccount->id)->first();
    expect($discountLine)->not->toBeNull()
        ->and((float) $discountLine->credit)->toBe(5000.0);

    expect((float) $entry->total_debit)->toBe((float) $entry->total_credit);

    $bill->refresh();
    expect((float) $bill->amount_due)->toBe(0.0)
        ->and($bill->status)->toBe('paid');
});

test('membatalkan payment AP dengan diskon mengembalikan sisa bill', function () {
    issue437Coa();
    $bill = issue437Bill(100000);
    $payment = ApPayment::factory()->create([
        'status' => 'draft',
        'confirmed_at' => null,
        'confirmed_by' => null,
        'supplier_id' => $bill->supplier_id,
        'branch_id' => issue437Branch(),
        'payment_number' => 'PAY-437-' . fake()->unique()->numerify('####'),
        'bank_account_id' => issue437Bank(),
        'payment_date' => '2026-03-10',
        'total_amount' => 95000,
    ]);
    ApPaymentAllocation::query()->create([
        'ap_payment_id' => $payment->id,
        'supplier_bill_id' => $bill->id,
        'allocated_amount' => 95000,
        'discount_taken' => 5000,
    ]);

    putJson("/api/ap-payments/{$payment->id}", ['status' => 'confirmed'])->assertOk();
    putJson("/api/ap-payments/{$payment->id}", ['status' => 'cancelled'])->assertOk();

    expect(ApPaymentAllocation::query()->where('ap_payment_id', $payment->id)->exists())->toBeFalse();
    expect((float) $bill->fresh()->amount_due)->toBe(100000.0);
});

test('posting receipt dengan diskon ditolak 422 bila akun diskon belum ada di COA', function () {
    issue437Coa(withDiscountAccounts: false);
    $invoice = issue437Invoice(100000);
    $receipt = ArReceipt::factory()->create([
        'status' => 'draft',
        'confirmed_at' => null,
        'confirmed_by' => null,
        'customer_id' => $invoice->customer_id,
        'branch_id' => issue437Branch(),
        'receipt_number' => 'RCP-437-' . fake()->unique()->numerify('####'),
        'bank_account_id' => issue437Bank(),
        'receipt_date' => '2026-03-10',
        'total_amount' => 95000,
    ]);
    ArReceiptAllocation::query()->create([
        'ar_receipt_id' => $receipt->id,
        'customer_invoice_id' => $invoice->id,
        'allocated_amount' => 95000,
        'discount_given' => 5000,
    ]);

    putJson("/api/ar-receipts/{$receipt->id}", ['status' => 'confirmed'])
        ->assertStatus(422);

    expect($receipt->fresh()->status)->toBe('draft');
});

test('jurnal receipt tanpa diskon tetap dua baris seperti sebelumnya', function () {
    issue437Coa();
    $invoice = issue437Invoice(100000);
    $receipt = ArReceipt::factory()->create([
        'status' => 'draft',
        'confirmed_at' => null,
        'confirmed_by' => null,
        'customer_id' => $invoice->customer_id,
        'branch_id' => issue437Branch(),
        'receipt_number' => 'RCP-437-' . fake()->unique()->numerify('####'),
        'bank_account_id' => issue437Bank(),
        'receipt_date' => '2026-03-10',
        'total_amount' => 100000,
    ]);
    ArReceiptAllocation::query()->create([
        'ar_receipt_id' => $receipt->id,
        'customer_invoice_id' => $invoice->id,
        'allocated_amount' => 100000,
        'discount_given' => 0,
    ]);

    putJson("/api/ar-receipts/{$receipt->id}", ['status' => 'confirmed'])->assertOk();

    $entry = JournalEntry::query()
        ->where('source_type', ArReceipt::class)
        ->where('source_id', $receipt->id)
        ->firstOrFail();

    expect($entry->lines()->count())->toBe(2);
});
