<?php

use App\Models\Account;
use App\Models\ApPayment;
use App\Models\ApPaymentAllocation;
use App\Models\ArReceipt;
use App\Models\ArReceiptAllocation;
use App\Models\CoaVersion;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Supplier;
use App\Models\SupplierBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    $early = ArReceipt::factory()->create([
        'customer_id' => $customer->id,
        'receipt_date' => '2026-02-01',
        'status' => 'confirmed',
    ]);
    $late = ArReceipt::factory()->create([
        'customer_id' => $customer->id,
        'receipt_date' => '2026-04-01',
        'status' => 'confirmed',
    ]);

    ArReceiptAllocation::create([
        'ar_receipt_id' => $early->id,
        'customer_invoice_id' => $invoice->id,
        'allocated_amount' => 30000,
        'discount_given' => 0,
    ]);
    ArReceiptAllocation::create([
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

test('ar aging counts credit notes dated on or before as_of_date only', function () {
    $customer = Customer::factory()->create();
    $invoice = CustomerInvoice::factory()->create([
        'invoice_number' => 'INV-CN-DATE',
        'customer_id' => $customer->id,
        'fiscal_year_id' => $this->fiscalYear->id,
        'invoice_date' => '2026-01-10',
        'due_date' => '2026-02-10',
        'status' => 'partially_paid',
        'grand_total' => 100000,
        'credit_note_amount' => 40000,
        'amount_due' => 60000,
    ]);

    // Credit note 40000 baru dibuat setelah as_of -> belum mengurangi sisa.
    CreditNote::factory()->confirmed()->create([
        'customer_id' => $customer->id,
        'customer_invoice_id' => $invoice->id,
        'fiscal_year_id' => $this->fiscalYear->id,
        'credit_note_date' => '2026-04-01',
        'grand_total' => 40000,
    ]);

    Sanctum::actingAs($this->arUser, ['*']);

    getJson('/api/reports/ar-aging?as_of_date=2026-03-15')
        ->assertOk()
        ->assertJsonPath('data.0.customer_invoice.invoice_number', 'INV-CN-DATE')
        ->assertJsonPath('summary.totals.amount_due', '100000.00');
});

test('ar aging keeps a document paid today when it was still open on as_of_date', function () {
    $customer = Customer::factory()->create();
    $invoice = CustomerInvoice::factory()->create([
        'invoice_number' => 'INV-PAID-LATER',
        'customer_id' => $customer->id,
        'fiscal_year_id' => $this->fiscalYear->id,
        'invoice_date' => '2026-01-10',
        'due_date' => '2026-02-10',
        'status' => 'paid',
        'grand_total' => 100000,
        'amount_received' => 100000,
        'amount_due' => 0,
    ]);

    $receipt = ArReceipt::factory()->create([
        'customer_id' => $customer->id,
        'receipt_date' => '2026-04-01',
        'status' => 'confirmed',
    ]);
    ArReceiptAllocation::create([
        'ar_receipt_id' => $receipt->id,
        'customer_invoice_id' => $invoice->id,
        'allocated_amount' => 100000,
        'discount_given' => 0,
    ]);

    Sanctum::actingAs($this->arUser, ['*']);

    getJson('/api/reports/ar-aging?as_of_date=2026-03-15')
        ->assertOk()
        ->assertJsonPath('data.0.customer_invoice.invoice_number', 'INV-PAID-LATER')
        ->assertJsonPath('summary.totals.amount_due', '100000.00');
});

test('ar aging amount_received on rows follows as_of_date', function () {
    $customer = Customer::factory()->create();
    $invoice = CustomerInvoice::factory()->create([
        'invoice_number' => 'INV-RCV-HIST',
        'customer_id' => $customer->id,
        'fiscal_year_id' => $this->fiscalYear->id,
        'invoice_date' => '2026-01-10',
        'due_date' => '2026-02-10',
        'status' => 'partially_paid',
        'grand_total' => 100000,
        'amount_received' => 60000,
        'amount_due' => 40000,
    ]);

    $early = ArReceipt::factory()->create([
        'customer_id' => $customer->id,
        'receipt_date' => '2026-02-01',
        'status' => 'confirmed',
    ]);
    $late = ArReceipt::factory()->create([
        'customer_id' => $customer->id,
        'receipt_date' => '2026-04-01',
        'status' => 'confirmed',
    ]);
    ArReceiptAllocation::create([
        'ar_receipt_id' => $early->id,
        'customer_invoice_id' => $invoice->id,
        'allocated_amount' => 30000,
        'discount_given' => 0,
    ]);
    ArReceiptAllocation::create([
        'ar_receipt_id' => $late->id,
        'customer_invoice_id' => $invoice->id,
        'allocated_amount' => 30000,
        'discount_given' => 0,
    ]);

    Sanctum::actingAs($this->arUser, ['*']);

    getJson('/api/reports/ar-aging?as_of_date=2026-03-15')
        ->assertOk()
        ->assertJsonPath('data.0.amounts.amount_received', '30000.00');
});

test('ap aging subtracts payments and discounts dated on or before as_of_date only', function () {
    $supplier = Supplier::factory()->create();
    $bill = SupplierBill::factory()->confirmed()->create([
        'bill_number' => 'BILL-HIST',
        'supplier_id' => $supplier->id,
        'fiscal_year_id' => $this->fiscalYear->id,
        'bill_date' => '2026-01-10',
        'due_date' => '2026-02-10',
        'grand_total' => 100000,
        'amount_paid' => 60000,
        'amount_due' => 40000,
    ]);

    $early = ApPayment::factory()->confirmed()->create([
        'supplier_id' => $supplier->id,
        'payment_date' => '2026-02-01',
    ]);
    $late = ApPayment::factory()->confirmed()->create([
        'supplier_id' => $supplier->id,
        'payment_date' => '2026-04-01',
    ]);

    ApPaymentAllocation::create([
        'ap_payment_id' => $early->id,
        'supplier_bill_id' => $bill->id,
        'allocated_amount' => 20000,
        'discount_taken' => 10000,
    ]);
    ApPaymentAllocation::create([
        'ap_payment_id' => $late->id,
        'supplier_bill_id' => $bill->id,
        'allocated_amount' => 30000,
        'discount_taken' => 0,
    ]);

    // Diskon hanya mengurangi sisa bila jurnal payment memuat akun diskon (#437).
    $earlyEntry = JournalEntry::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'status' => 'posted',
        'source_type' => ApPayment::class,
        'source_id' => $early->id,
    ]);
    $discountAccount = Account::factory()->create([
        'coa_version_id' => CoaVersion::factory()->create(['status' => 'active'])->id,
        'code' => '51900',
        'name' => 'Purchase Discount',
        'type' => 'expense',
        'normal_balance' => 'credit',
        'is_active' => true,
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $earlyEntry->id,
        'account_id' => $discountAccount->id,
        'debit' => 0,
        'credit' => 10000,
    ]);
    $early->forceFill(['journal_entry_id' => $earlyEntry->id])->save();

    Sanctum::actingAs($this->apUser, ['*']);

    // Per 2026-03-15: hanya alokasi 20000 + diskon 10000 (terjurnal) yang berlaku -> sisa 70000.
    getJson('/api/reports/ap-aging?as_of_date=2026-03-15')
        ->assertOk()
        ->assertJsonPath('data.0.bill.number', 'BILL-HIST')
        ->assertJsonPath('summary.totals.amount_due', '70000.00');
});

test('ap aging keeps a bill paid today when it was still open on as_of_date', function () {
    $supplier = Supplier::factory()->create();
    $bill = SupplierBill::factory()->paid()->create([
        'bill_number' => 'BILL-PAID-LATER',
        'supplier_id' => $supplier->id,
        'fiscal_year_id' => $this->fiscalYear->id,
        'bill_date' => '2026-01-10',
        'due_date' => '2026-02-10',
        'grand_total' => 100000,
        'amount_paid' => 100000,
        'amount_due' => 0,
    ]);

    $payment = ApPayment::factory()->confirmed()->create([
        'supplier_id' => $supplier->id,
        'payment_date' => '2026-04-01',
    ]);
    ApPaymentAllocation::create([
        'ap_payment_id' => $payment->id,
        'supplier_bill_id' => $bill->id,
        'allocated_amount' => 100000,
        'discount_taken' => 0,
    ]);

    Sanctum::actingAs($this->apUser, ['*']);

    getJson('/api/reports/ap-aging?as_of_date=2026-03-15')
        ->assertOk()
        ->assertJsonPath('data.0.bill.number', 'BILL-PAID-LATER')
        ->assertJsonPath('summary.totals.amount_due', '100000.00');
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
