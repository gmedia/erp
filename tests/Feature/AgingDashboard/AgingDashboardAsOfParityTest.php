<?php

use App\Models\Account;
use App\Models\ApPayment;
use App\Models\ApPaymentAllocation;
use App\Models\ArReceipt;
use App\Models\ArReceiptAllocation;
use App\Models\CoaVersion;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Supplier;
use App\Models\SupplierBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Traits\CreatesTestUserWithPermissions;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class, CreatesTestUserWithPermissions::class)->group('aging-dashboard');

beforeEach(function () {
    $this->fiscalYear = FiscalYear::factory()->create();
});

/**
 * Akun diskon di COA aktif. Diskon dianggap "terjurnal" hanya bila jurnal receipt/payment
 * memuat baris pada akun ini; data lama tanpa jurnal diskon tidak mengurangi sisa.
 */
function asOfParityCoa(): void
{
    $coa = CoaVersion::factory()->create(['status' => 'active']);

    foreach ([
        ['11200', 'Accounts Receivable', 'asset', 'debit'],
        ['21100', 'Accounts Payable', 'liability', 'credit'],
        ['41900', 'Sales Discount', 'revenue', 'debit'],
        ['51900', 'Purchase Discount', 'expense', 'credit'],
    ] as [$code, $name, $type, $normal]) {
        Account::factory()->create([
            'coa_version_id' => $coa->id,
            'code' => $code,
            'name' => $name,
            'type' => $type,
            'normal_balance' => $normal,
            'is_active' => true,
        ]);
    }
}

test('dashboard AR total sama dengan laporan AR aging untuk as_of yang sama', function () {
    $customer = Customer::factory()->create();
    $invoice = CustomerInvoice::factory()->create([
        'invoice_number' => 'INV-PARITY',
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
    ArReceiptAllocation::create([
        'ar_receipt_id' => $early->id,
        'customer_invoice_id' => $invoice->id,
        'allocated_amount' => 30000,
        'discount_given' => 0,
    ]);

    Sanctum::actingAs($this->createTestUserWithPermissions(['aging_dashboard', 'ar_aging_report']), ['*']);
    $report = getJson('/api/reports/ar-aging?as_of_date=2026-03-15')->assertOk();

    Sanctum::actingAs($this->createTestUserWithPermissions(['aging_dashboard', 'ar_aging_report']), ['*']);
    $dashboard = getJson('/api/aging-dashboard?as_of_date=2026-03-15')->assertOk();

    // Historis per 2026-03-15: 100000 - 30000 = 70000, bukan amount_due tersimpan 40000.
    expect($report->json('summary.totals.amount_due'))->toBe('70000.00')
        ->and($dashboard->json('ar_summary.total_outstanding'))->toEqual(70000)
        ->and($dashboard->json('ar_summary.invoice_count'))->toBe(1);
});

test('dashboard mengabaikan invoice setelah as_of seperti laporan AR', function () {
    $customer = Customer::factory()->create();
    CustomerInvoice::factory()->create([
        'invoice_number' => 'INV-AFTER-ASOF',
        'customer_id' => $customer->id,
        'fiscal_year_id' => $this->fiscalYear->id,
        'invoice_date' => '2026-04-10',
        'due_date' => '2026-05-10',
        'status' => 'sent',
        'grand_total' => 100000,
        'amount_due' => 100000,
    ]);

    Sanctum::actingAs($this->createTestUserWithPermissions(['aging_dashboard', 'ar_aging_report']), ['*']);
    $report = getJson('/api/reports/ar-aging?as_of_date=2026-03-15')->assertOk();
    $dashboard = getJson('/api/aging-dashboard?as_of_date=2026-03-15')->assertOk();

    expect($report->json('summary.totals.amount_due'))->toBe('0.00')
        ->and($dashboard->json('ar_summary.total_outstanding'))->toEqual(0)
        ->and($dashboard->json('ar_summary.invoice_count'))->toBe(0);
});

test('dashboard AP total sama dengan laporan AP aging untuk as_of yang sama', function () {
    $supplier = Supplier::factory()->create();
    $bill = SupplierBill::factory()->confirmed()->create([
        'bill_number' => 'BILL-PARITY',
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
    ApPaymentAllocation::create([
        'ap_payment_id' => $early->id,
        'supplier_bill_id' => $bill->id,
        'allocated_amount' => 20000,
        'discount_taken' => 0,
    ]);

    Sanctum::actingAs($this->createTestUserWithPermissions(['aging_dashboard', 'ap_aging_report']), ['*']);
    $report = getJson('/api/reports/ap-aging?as_of_date=2026-03-15')->assertOk();

    Sanctum::actingAs($this->createTestUserWithPermissions(['aging_dashboard', 'ap_aging_report']), ['*']);
    $dashboard = getJson('/api/aging-dashboard?as_of_date=2026-03-15')->assertOk();

    expect($report->json('summary.totals.amount_due'))->toBe('80000.00')
        ->and($dashboard->json('ap_summary.total_outstanding'))->toEqual(80000);
});

test('diskon AR tanpa jurnal diskon tidak mengurangi sisa di dashboard maupun laporan', function () {
    asOfParityCoa();

    $customer = Customer::factory()->create();
    $invoice = CustomerInvoice::factory()->create([
        'invoice_number' => 'INV-LEGACY-DISC',
        'customer_id' => $customer->id,
        'fiscal_year_id' => $this->fiscalYear->id,
        'invoice_date' => '2026-01-10',
        'due_date' => '2026-02-10',
        'status' => 'partially_paid',
        'grand_total' => 100000,
        'amount_received' => 95000,
        'amount_due' => 5000,
    ]);

    // Data lama: pelunasan terposting dengan diskon tersimpan, tetapi jurnalnya tidak
    // memuat akun diskon (sebelum #437 dijurnal).
    $receipt = ArReceipt::factory()->create([
        'customer_id' => $customer->id,
        'receipt_date' => '2026-02-01',
        'status' => 'confirmed',
    ]);
    ArReceiptAllocation::create([
        'ar_receipt_id' => $receipt->id,
        'customer_invoice_id' => $invoice->id,
        'allocated_amount' => 95000,
        'discount_given' => 5000,
    ]);
    $entry = JournalEntry::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'status' => 'posted',
        'source_type' => ArReceipt::class,
        'source_id' => $receipt->id,
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $entry->id,
        'account_id' => Account::query()->where('code', '11200')->value('id'),
        'debit' => 0,
        'credit' => 95000,
    ]);
    $receipt->forceFill(['journal_entry_id' => $entry->id])->save();

    Sanctum::actingAs($this->createTestUserWithPermissions(['aging_dashboard', 'ar_aging_report']), ['*']);
    $report = getJson('/api/reports/ar-aging?as_of_date=2026-03-15')->assertOk();

    Sanctum::actingAs($this->createTestUserWithPermissions(['aging_dashboard', 'ar_aging_report']), ['*']);
    $dashboard = getJson('/api/aging-dashboard?as_of_date=2026-03-15')->assertOk();

    // Diskon 5000 tidak terjurnal -> tidak dihitung: sisa tetap 5000 (= laporan).
    expect($report->json('summary.totals.amount_due'))->toBe('5000.00')
        ->and($dashboard->json('ar_summary.total_outstanding'))->toEqual(5000);
});
