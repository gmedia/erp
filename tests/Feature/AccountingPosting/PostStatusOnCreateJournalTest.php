<?php

use App\Actions\AccountingPosting\PostArReceiptJournalAction;
use App\Actions\AccountingPosting\PostCustomerInvoiceJournalAction;
use App\Models\Account;
use App\Models\ArReceipt;
use App\Models\Branch;
use App\Models\CoaVersion;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('customer-invoices', 'ar-receipts', 'credit-notes');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'customer_invoice',
        'customer_invoice.create',
        'ar_receipt',
        'ar_receipt.create',
    ]);
    Sanctum::actingAs($user, ['*']);
});

/**
 * Fixture COA aktif per tanggal (sesuai #357): akun piutang 11200, pendapatan 41100,
 * kas/bank 11110. Dipakai untuk POST dokumen berstatus terposting (Refs #367).
 *
 * @return array{customer: Customer, branch: Branch, fiscal_year: FiscalYear, product: Product, unit: Unit, revenue: Account, bank: Account}
 */
function makePostStatusOnCreateFixture(): array
{
    FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);
    $coaVersion = CoaVersion::factory()->create(['status' => 'active']);

    Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '11200',
        'name' => 'Accounts Receivable',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);
    $revenue = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '41100',
        'name' => 'Pendapatan Penjualan',
        'type' => 'revenue',
        'normal_balance' => 'credit',
        'is_active' => true,
    ]);
    $bank = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '11110',
        'name' => 'Kas Bank',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);

    return [
        'customer' => Customer::factory()->create(),
        'branch' => Branch::factory()->create(),
        'fiscal_year' => FiscalYear::query()->where('name', '2026')->firstOrFail(),
        'product' => Product::factory()->create(),
        'unit' => Unit::factory()->create(),
        'revenue' => $revenue,
        'bank' => $bank,
    ];
}

test('POST customer invoice with status sent posts journal in the same request (Refs #367)', function () {
    $fixture = makePostStatusOnCreateFixture();

    $response = postJson('/api/customer-invoices', [
        'customer_id' => $fixture['customer']->id,
        'branch_id' => $fixture['branch']->id,
        'fiscal_year_id' => $fixture['fiscal_year']->id,
        'invoice_date' => '2026-03-06',
        'due_date' => '2026-04-06',
        'currency' => 'IDR',
        'status' => 'sent',
        'items' => [
            [
                'product_id' => $fixture['product']->id,
                'unit_id' => $fixture['unit']->id,
                'account_id' => $fixture['revenue']->id,
                'description' => 'Jasa',
                'quantity' => 1,
                'unit_price' => 100000,
                'discount_percent' => 0,
                'tax_percent' => 0,
            ],
        ],
    ]);

    $response->assertCreated();

    $invoice = CustomerInvoice::query()->findOrFail($response->json('data.id'));

    expect($invoice->journal_entry_id)->not->toBeNull();
    expect(JournalEntry::query()->findOrFail($invoice->journal_entry_id)->status)->toBe('posted');
});

test('POST customer invoice with status sent rolls back when journal posting fails (Refs #367)', function () {
    $fixture = makePostStatusOnCreateFixture();

    $this->app->bind(PostCustomerInvoiceJournalAction::class, function () {
        return new class
        {
            public function execute(CustomerInvoice $customerInvoice): void
            {
                throw new RuntimeException('Simulated journal posting failure');
            }
        };
    });

    postJson('/api/customer-invoices', [
        'customer_id' => $fixture['customer']->id,
        'branch_id' => $fixture['branch']->id,
        'fiscal_year_id' => $fixture['fiscal_year']->id,
        'invoice_date' => '2026-03-06',
        'due_date' => '2026-04-06',
        'currency' => 'IDR',
        'status' => 'sent',
        'items' => [
            [
                'product_id' => $fixture['product']->id,
                'unit_id' => $fixture['unit']->id,
                'account_id' => $fixture['revenue']->id,
                'description' => 'Jasa gagal',
                'quantity' => 1,
                'unit_price' => 100000,
                'discount_percent' => 0,
                'tax_percent' => 0,
            ],
        ],
    ])->assertStatus(500);

    expect(DB::table('customer_invoices')->where('status', 'sent')->count())->toBe(0);
});

test('POST AR receipt with status confirmed posts journal in the same request (Refs #367)', function () {
    $fixture = makePostStatusOnCreateFixture();
    $invoice = CustomerInvoice::factory()->sent()->create([
        'customer_id' => $fixture['customer']->id,
        'branch_id' => $fixture['branch']->id,
        'fiscal_year_id' => $fixture['fiscal_year']->id,
        'grand_total' => 500000,
        'amount_received' => 0,
        'amount_due' => 500000,
    ]);

    $response = postJson('/api/ar-receipts', [
        'customer_id' => $fixture['customer']->id,
        'branch_id' => $fixture['branch']->id,
        'fiscal_year_id' => $fixture['fiscal_year']->id,
        'receipt_date' => '2026-03-15',
        'payment_method' => 'bank_transfer',
        'bank_account_id' => $fixture['bank']->id,
        'currency' => 'IDR',
        'total_amount' => 500000,
        'reference' => 'RCV-367-001',
        'status' => 'confirmed',
        'allocations' => [
            [
                'customer_invoice_id' => $invoice->id,
                'allocated_amount' => 500000,
            ],
        ],
    ]);

    $response->assertCreated();

    $receipt = ArReceipt::query()->findOrFail($response->json('data.id'));

    expect($receipt->journal_entry_id)->not->toBeNull();
    expect(JournalEntry::query()->findOrFail($receipt->journal_entry_id)->status)->toBe('posted');
});

test('POST AR receipt with status confirmed rolls back when journal posting fails (Refs #367)', function () {
    $fixture = makePostStatusOnCreateFixture();
    $invoice = CustomerInvoice::factory()->sent()->create([
        'customer_id' => $fixture['customer']->id,
        'branch_id' => $fixture['branch']->id,
        'fiscal_year_id' => $fixture['fiscal_year']->id,
        'grand_total' => 500000,
        'amount_received' => 0,
        'amount_due' => 500000,
    ]);

    $this->app->bind(PostArReceiptJournalAction::class, function () {
        return new class
        {
            public function execute(ArReceipt $arReceipt): void
            {
                throw new RuntimeException('Simulated journal posting failure');
            }
        };
    });

    postJson('/api/ar-receipts', [
        'customer_id' => $fixture['customer']->id,
        'branch_id' => $fixture['branch']->id,
        'fiscal_year_id' => $fixture['fiscal_year']->id,
        'receipt_date' => '2026-03-15',
        'payment_method' => 'bank_transfer',
        'bank_account_id' => $fixture['bank']->id,
        'currency' => 'IDR',
        'total_amount' => 500000,
        'reference' => 'RCV-367-002',
        'status' => 'confirmed',
        'allocations' => [
            [
                'customer_invoice_id' => $invoice->id,
                'allocated_amount' => 500000,
            ],
        ],
    ])->assertStatus(500);

    assertDatabaseMissing('ar_receipts', ['reference' => 'RCV-367-002']);
});
