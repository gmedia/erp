<?php

use App\Models\Account;
use App\Models\Branch;
use App\Models\CoaVersion;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('ar-receipts');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'ar_receipt',
        'ar_receipt.create',
    ]);
    Sanctum::actingAs($user, ['*']);

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
    $this->bankAccount = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '11110',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);
    $this->customer = Customer::factory()->create();
    $this->branch = Branch::factory()->create();
    $this->fiscalYear = FiscalYear::query()->where('name', '2026')->firstOrFail();
});

test('creating a receipt with a payment-result status is rejected with 422', function (string $status) {
    $invoice = CustomerInvoice::factory()->create([
        'customer_id' => $this->customer->id,
        'grand_total' => 1000000,
        'amount_received' => 0,
        'amount_due' => 1000000,
        'credit_note_amount' => 0,
        'status' => 'sent',
    ]);

    postJson('/api/ar-receipts', [
        'customer_id' => $this->customer->id,
        'branch_id' => $this->branch->id,
        'fiscal_year_id' => $this->fiscalYear->id,
        'receipt_date' => '2026-03-06',
        'payment_method' => 'bank_transfer',
        'bank_account_id' => $this->bankAccount->id,
        'currency' => 'IDR',
        'total_amount' => 500000,
        'status' => $status,
        'allocations' => [
            ['customer_invoice_id' => $invoice->id, 'allocated_amount' => 500000],
        ],
    ])->assertStatus(422)->assertJsonValidationErrors(['status']);

    expect((float) $invoice->refresh()->amount_received)->toBe(0.0)
        ->and($invoice->status)->toBe('sent');
})->with(['reconciled']);
