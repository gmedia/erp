<?php

use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\FiscalYear;
use App\Models\Supplier;
use App\Models\SupplierBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class)->group('ar-aging-report');

beforeEach(function () {
    $this->arUser = createTestUserWithPermissions(['ar_aging_report']);
    $this->apUser = createTestUserWithPermissions(['ap_aging_report']);
    $this->fiscalYear = FiscalYear::factory()->create();
});

test('ar aging summary as_of_date follows the requested as_of_date', function () {
    Sanctum::actingAs($this->arUser, ['*']);

    getJson('/api/reports/ar-aging?as_of_date=2026-03-15')
        ->assertOk()
        ->assertJsonPath('summary.as_of_date', '2026-03-15');
});

test('ar aging excludes invoices dated after as_of_date', function () {
    $customer = Customer::factory()->create();

    CustomerInvoice::factory()->create([
        'invoice_number' => 'INV-FUTURE',
        'customer_id' => $customer->id,
        'fiscal_year_id' => $this->fiscalYear->id,
        'invoice_date' => '2026-04-10',
        'due_date' => '2026-05-10',
        'status' => 'sent',
        'grand_total' => 100000,
        'amount_due' => 100000,
    ]);

    Sanctum::actingAs($this->arUser, ['*']);

    getJson('/api/reports/ar-aging?as_of_date=2026-03-15')
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('summary.totals.amount_due', '0.00');
});

test('ar aging buckets are computed relative to as_of_date', function () {
    $customer = Customer::factory()->create();

    CustomerInvoice::factory()->create([
        'invoice_number' => 'INV-BUCKET',
        'customer_id' => $customer->id,
        'fiscal_year_id' => $this->fiscalYear->id,
        'invoice_date' => '2026-01-01',
        'due_date' => '2026-02-01',
        'status' => 'sent',
        'grand_total' => 100000,
        'amount_due' => 100000,
    ]);

    Sanctum::actingAs($this->arUser, ['*']);

    // 2026-03-15 minus due 2026-02-01 = 42 hari -> bucket 31_60.
    getJson('/api/reports/ar-aging?as_of_date=2026-03-15')
        ->assertOk()
        ->assertJsonPath('summary.totals.aging_31_60', '100000.00')
        ->assertJsonPath('summary.totals.aging_current', '0.00');
});

test('ar aging historical balance subtracts receipts dated on or before as_of_date only', function () {
    $customer = Customer::factory()->create();
    $invoice = CustomerInvoice::factory()->create([
        'invoice_number' => 'INV-HIST',
        'customer_id' => $customer->id,
        'fiscal_year_id' => $this->fiscalYear->id,
        'invoice_date' => '2026-01-10',
        'due_date' => '2026-02-10',
        'status' => 'partially_paid',
        'grand_total' => 100000,
        'amount_received' => 60000,
        'amount_due' => 40000,
    ]);

    $early = \App\Models\ArReceipt::factory()->create([
        'customer_id' => $customer->id,
        'receipt_date' => '2026-02-01',
        'status' => 'confirmed',
    ]);
    $late = \App\Models\ArReceipt::factory()->create([
        'customer_id' => $customer->id,
        'receipt_date' => '2026-04-01',
        'status' => 'confirmed',
    ]);

    \App\Models\ArReceiptAllocation::create([
        'ar_receipt_id' => $early->id,
        'customer_invoice_id' => $invoice->id,
        'allocated_amount' => 30000,
        'discount_given' => 0,
    ]);
    \App\Models\ArReceiptAllocation::create([
        'ar_receipt_id' => $late->id,
        'customer_invoice_id' => $invoice->id,
        'allocated_amount' => 30000,
        'discount_given' => 0,
    ]);

    Sanctum::actingAs($this->arUser, ['*']);

    // Per 2026-03-15: hanya pelunasan 30000 (2026-02-01) yang berlaku -> sisa 70000.
    getJson('/api/reports/ar-aging?as_of_date=2026-03-15')
        ->assertOk()
        ->assertJsonPath('data.0.customer_invoice.invoice_number', 'INV-HIST')
        ->assertJsonPath('summary.totals.amount_due', '70000.00');
});

test('ap aging excludes bills dated after as_of_date', function () {
    $supplier = Supplier::factory()->create();

    SupplierBill::factory()->confirmed()->create([
        'bill_number' => 'BILL-FUTURE',
        'supplier_id' => $supplier->id,
        'fiscal_year_id' => $this->fiscalYear->id,
        'bill_date' => '2026-04-10',
        'due_date' => '2026-05-10',
        'grand_total' => 100000,
        'amount_due' => 100000,
    ]);

    Sanctum::actingAs($this->apUser, ['*']);

    getJson('/api/reports/ap-aging?as_of_date=2026-03-15')
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('summary.as_of_date', '2026-03-15');
});

test('ap aging buckets are computed relative to as_of_date', function () {
    $supplier = Supplier::factory()->create();

    SupplierBill::factory()->confirmed()->create([
        'bill_number' => 'BILL-BUCKET',
        'supplier_id' => $supplier->id,
        'fiscal_year_id' => $this->fiscalYear->id,
        'bill_date' => '2026-01-01',
        'due_date' => '2026-02-01',
        'grand_total' => 100000,
        'amount_due' => 100000,
    ]);

    Sanctum::actingAs($this->apUser, ['*']);

    getJson('/api/reports/ap-aging?as_of_date=2026-03-15')
        ->assertOk()
        ->assertJsonPath('summary.totals.days_31_60', '100000.00')
        ->assertJsonPath('summary.totals.current_amount', '0.00');
});
