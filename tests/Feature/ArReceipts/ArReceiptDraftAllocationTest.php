<?php

use App\Models\Account;
use App\Models\ArReceipt;
use App\Models\Branch;
use App\Models\CoaVersion;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('ar-receipts');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'ar_receipt',
        'ar_receipt.create',
        'ar_receipt.edit',
        'ar_receipt.delete',
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

/**
 * Invoice 1.000.000 (sent) dengan sisa 1.000.000.
 */
function arDraftInvoice(Customer $customer, float $grandTotal = 1000000): CustomerInvoice
{
    return CustomerInvoice::factory()->create([
        'customer_id' => $customer->id,
        'grand_total' => $grandTotal,
        'amount_received' => 0,
        'amount_due' => $grandTotal,
        'credit_note_amount' => 0,
        'status' => 'sent',
    ]);
}

function arDraftReceiptPayload(Customer $customer, Account $bank, array $overrides = []): array
{
    return array_merge([
        'customer_id' => $customer->id,
        'branch_id' => test()->branch->id,
        'fiscal_year_id' => test()->fiscalYear->id,
        'receipt_date' => '2026-03-06',
        'payment_method' => 'bank_transfer',
        'bank_account_id' => $bank->id,
        'currency' => 'IDR',
        'total_amount' => 500000,
        'status' => 'draft',
    ], $overrides);
}

test('draft receipt allocation does not change invoice due or status', function () {
    $invoice = arDraftInvoice($this->customer);

    postJson('/api/ar-receipts', arDraftReceiptPayload($this->customer, $this->bankAccount, [
        'allocations' => [
            ['customer_invoice_id' => $invoice->id, 'allocated_amount' => 500000],
        ],
    ]))->assertCreated();

    $invoice->refresh();
    expect((float) $invoice->amount_received)->toBe(0.0)
        ->and((float) $invoice->amount_due)->toBe(1000000.0)
        ->and($invoice->status)->toBe('sent');
});

test('confirming a draft receipt with its own allocation succeeds', function () {
    $invoice = arDraftInvoice($this->customer);

    $receipt = postJson('/api/ar-receipts', arDraftReceiptPayload($this->customer, $this->bankAccount, [
        'allocations' => [
            ['customer_invoice_id' => $invoice->id, 'allocated_amount' => 500000],
        ],
    ]))->assertCreated()->json('data');

    putJson('/api/ar-receipts/' . $receipt['id'], [
        'status' => 'confirmed',
        'allocations' => [
            ['customer_invoice_id' => $invoice->id, 'allocated_amount' => 500000],
        ],
    ])->assertOk()->assertJsonPath('data.status', 'confirmed');

    $invoice->refresh();
    expect((float) $invoice->amount_received)->toBe(500000.0)
        ->and((float) $invoice->amount_due)->toBe(500000.0)
        ->and($invoice->status)->toBe('partially_paid');
});

test('cancelling a confirmed receipt restores invoice due', function () {
    $invoice = arDraftInvoice($this->customer);

    $receipt = postJson('/api/ar-receipts', arDraftReceiptPayload($this->customer, $this->bankAccount, [
        'allocations' => [
            ['customer_invoice_id' => $invoice->id, 'allocated_amount' => 500000],
        ],
    ]))->assertCreated()->json('data');

    putJson('/api/ar-receipts/' . $receipt['id'], [
        'status' => 'confirmed',
        'allocations' => [
            ['customer_invoice_id' => $invoice->id, 'allocated_amount' => 500000],
        ],
    ])->assertOk();

    putJson('/api/ar-receipts/' . $receipt['id'], [
        'status' => 'cancelled',
    ])->assertOk();

    $invoice->refresh();
    expect((float) $invoice->amount_received)->toBe(0.0)
        ->and((float) $invoice->amount_due)->toBe(1000000.0)
        ->and($invoice->status)->toBe('sent');
});

test('confirm without allocations payload still rejects draft exceeding remaining balance', function () {
    $invoice = arDraftInvoice($this->customer);

    $first = postJson('/api/ar-receipts', arDraftReceiptPayload($this->customer, $this->bankAccount, [
        'allocations' => [
            ['customer_invoice_id' => $invoice->id, 'allocated_amount' => 800000],
        ],
    ]))->assertCreated()->json('data');

    $second = postJson('/api/ar-receipts', arDraftReceiptPayload($this->customer, $this->bankAccount, [
        'total_amount' => 800000,
        'allocations' => [
            ['customer_invoice_id' => $invoice->id, 'allocated_amount' => 800000],
        ],
    ]))->assertCreated()->json('data');

    putJson('/api/ar-receipts/' . $first['id'], [
        'status' => 'confirmed',
        'allocations' => [
            ['customer_invoice_id' => $invoice->id, 'allocated_amount' => 800000],
        ],
    ])->assertOk();

    putJson('/api/ar-receipts/' . $second['id'], [
        'status' => 'confirmed',
    ])->assertStatus(422);

    $invoice->refresh();
    expect((float) $invoice->amount_received)->toBe(800000.0)
        ->and(ArReceipt::find($second['id'])->status)->toBe('draft');
});

test('confirming with allocations persists them on the receipt', function () {
    $invoice = arDraftInvoice($this->customer);

    $receipt = postJson('/api/ar-receipts', arDraftReceiptPayload($this->customer, $this->bankAccount, [
        'allocations' => [
            ['customer_invoice_id' => $invoice->id, 'allocated_amount' => 500000],
        ],
    ]))->assertCreated()->json('data');

    putJson('/api/ar-receipts/' . $receipt['id'], [
        'status' => 'confirmed',
        'allocations' => [
            ['customer_invoice_id' => $invoice->id, 'allocated_amount' => 500000],
        ],
    ])->assertOk();

    $stored = ArReceipt::find($receipt['id'])->allocations()->get();
    expect($stored)->toHaveCount(1)
        ->and((float) $stored->first()->allocated_amount)->toBe(500000.0);
});

test('put may resend an unchanged reconciled status and may reconcile a confirmed receipt', function () {
    $invoice = arDraftInvoice($this->customer);

    $receipt = postJson('/api/ar-receipts', arDraftReceiptPayload($this->customer, $this->bankAccount, [
        'allocations' => [
            ['customer_invoice_id' => $invoice->id, 'allocated_amount' => 500000],
        ],
    ]))->assertCreated()->json('data');

    putJson('/api/ar-receipts/' . $receipt['id'], [
        'status' => 'confirmed',
        'allocations' => [
            ['customer_invoice_id' => $invoice->id, 'allocated_amount' => 500000],
        ],
    ])->assertOk();

    putJson('/api/ar-receipts/' . $receipt['id'], [
        'status' => 'reconciled',
    ])->assertOk()->assertJsonPath('data.status', 'reconciled');

    putJson('/api/ar-receipts/' . $receipt['id'], [
        'status' => 'reconciled',
        'customer_id' => $this->customer->id,
    ])->assertOk()->assertJsonPath('data.status', 'reconciled');
});

test('second draft exceeding remaining balance is rejected with 422 on confirm', function () {
    $invoice = arDraftInvoice($this->customer);

    $first = postJson('/api/ar-receipts', arDraftReceiptPayload($this->customer, $this->bankAccount, [
        'allocations' => [
            ['customer_invoice_id' => $invoice->id, 'allocated_amount' => 800000],
        ],
    ]))->assertCreated()->json('data');

    $second = postJson('/api/ar-receipts', arDraftReceiptPayload($this->customer, $this->bankAccount, [
        'total_amount' => 800000,
        'allocations' => [
            ['customer_invoice_id' => $invoice->id, 'allocated_amount' => 800000],
        ],
    ]))->assertCreated()->json('data');

    putJson('/api/ar-receipts/' . $first['id'], [
        'status' => 'confirmed',
        'allocations' => [
            ['customer_invoice_id' => $invoice->id, 'allocated_amount' => 800000],
        ],
    ])->assertOk();

    putJson('/api/ar-receipts/' . $second['id'], [
        'status' => 'confirmed',
        'allocations' => [
            ['customer_invoice_id' => $invoice->id, 'allocated_amount' => 800000],
        ],
    ])->assertStatus(422);

    $invoice->refresh();
    expect((float) $invoice->amount_received)->toBe(800000.0)
        ->and((float) $invoice->amount_due)->toBe(200000.0);
    expect(ArReceipt::find($second['id'])->status)->toBe('draft');
});
