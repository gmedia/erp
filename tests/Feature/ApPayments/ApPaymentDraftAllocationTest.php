<?php

use App\Models\Account;
use App\Models\ApPayment;
use App\Models\Branch;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\Supplier;
use App\Models\ApPaymentAllocation;
use App\Models\SupplierBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('ap-payments');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'ap_payment',
        'ap_payment.create',
        'ap_payment.edit',
        'ap_payment.delete',
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
        'code' => '21100',
        'name' => 'Accounts Payable',
        'type' => 'liability',
        'normal_balance' => 'credit',
        'is_active' => true,
    ]);
    $this->bankAccount = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '11110',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);
    $this->supplier = Supplier::factory()->create();
    $this->branch = Branch::factory()->create();
    $this->fiscalYear = FiscalYear::query()->where('name', '2026')->firstOrFail();
});

/**
 * Bill 1.000.000 (confirmed) dengan sisa 1.000.000.
 */
function apDraftBill(Supplier $supplier, float $grandTotal = 1000000): SupplierBill
{
    return SupplierBill::factory()->create([
        'supplier_id' => $supplier->id,
        'grand_total' => $grandTotal,
        'amount_paid' => 0,
        'amount_due' => $grandTotal,
        'status' => 'confirmed',
    ]);
}

function apDraftPaymentPayload(Supplier $supplier, Account $bank, array $overrides = []): array
{
    return array_merge([
        'supplier_id' => $supplier->id,
        'branch_id' => test()->branch->id,
        'fiscal_year_id' => test()->fiscalYear->id,
        'payment_date' => '2026-03-06',
        'payment_method' => 'bank_transfer',
        'bank_account_id' => $bank->id,
        'currency' => 'IDR',
        'total_amount' => 500000,
        'status' => 'draft',
    ], $overrides);
}

test('draft payment allocation does not change bill due or status', function () {
    $bill = apDraftBill($this->supplier);

    postJson('/api/ap-payments', apDraftPaymentPayload($this->supplier, $this->bankAccount, [
        'allocations' => [
            ['supplier_bill_id' => $bill->id, 'allocated_amount' => 500000],
        ],
    ]))->assertCreated();

    $bill->refresh();
    expect((float) $bill->amount_paid)->toBe(0.0)
        ->and((float) $bill->amount_due)->toBe(1000000.0)
        ->and($bill->status)->toBe('confirmed');
});

test('confirming a draft payment with its own allocation succeeds', function () {
    $bill = apDraftBill($this->supplier);

    $payment = postJson('/api/ap-payments', apDraftPaymentPayload($this->supplier, $this->bankAccount, [
        'allocations' => [
            ['supplier_bill_id' => $bill->id, 'allocated_amount' => 500000],
        ],
    ]))->assertCreated()->json('data');

    putJson('/api/ap-payments/' . $payment['id'], [
        'status' => 'confirmed',
        'allocations' => [
            ['supplier_bill_id' => $bill->id, 'allocated_amount' => 500000],
        ],
    ])->assertOk()->assertJsonPath('data.status', 'confirmed');

    $bill->refresh();
    expect((float) $bill->amount_paid)->toBe(500000.0)
        ->and((float) $bill->amount_due)->toBe(500000.0)
        ->and($bill->status)->toBe('partially_paid');
});

test('cancelling a confirmed payment restores bill due', function () {
    $bill = apDraftBill($this->supplier);

    $payment = postJson('/api/ap-payments', apDraftPaymentPayload($this->supplier, $this->bankAccount, [
        'allocations' => [
            ['supplier_bill_id' => $bill->id, 'allocated_amount' => 500000],
        ],
    ]))->assertCreated()->json('data');

    putJson('/api/ap-payments/' . $payment['id'], [
        'status' => 'confirmed',
        'allocations' => [
            ['supplier_bill_id' => $bill->id, 'allocated_amount' => 500000],
        ],
    ])->assertOk();

    putJson('/api/ap-payments/' . $payment['id'], [
        'status' => 'cancelled',
    ])->assertOk();

    $bill->refresh();
    expect((float) $bill->amount_paid)->toBe(0.0)
        ->and((float) $bill->amount_due)->toBe(1000000.0)
        ->and($bill->status)->toBe('confirmed');
});

test('second draft exceeding remaining balance is rejected with 422 on confirm', function () {
    $bill = apDraftBill($this->supplier);

    $first = postJson('/api/ap-payments', apDraftPaymentPayload($this->supplier, $this->bankAccount, [
        'total_amount' => 800000,
        'allocations' => [
            ['supplier_bill_id' => $bill->id, 'allocated_amount' => 800000],
        ],
    ]))->assertCreated()->json('data');

    $second = postJson('/api/ap-payments', apDraftPaymentPayload($this->supplier, $this->bankAccount, [
        'total_amount' => 800000,
        'allocations' => [
            ['supplier_bill_id' => $bill->id, 'allocated_amount' => 800000],
        ],
    ]))->assertCreated()->json('data');

    putJson('/api/ap-payments/' . $first['id'], [
        'status' => 'confirmed',
        'allocations' => [
            ['supplier_bill_id' => $bill->id, 'allocated_amount' => 800000],
        ],
    ])->assertOk();

    putJson('/api/ap-payments/' . $second['id'], [
        'status' => 'confirmed',
        'allocations' => [
            ['supplier_bill_id' => $bill->id, 'allocated_amount' => 800000],
        ],
    ])->assertStatus(422);

    $bill->refresh();
    expect((float) $bill->amount_paid)->toBe(800000.0)
        ->and((float) $bill->amount_due)->toBe(200000.0);
    expect(ApPayment::find($second['id'])->status)->toBe('draft');
});

test('confirm without allocations payload still rejects draft exceeding remaining balance', function () {
    $bill = apDraftBill($this->supplier);

    $first = postJson('/api/ap-payments', apDraftPaymentPayload($this->supplier, $this->bankAccount, [
        'total_amount' => 800000,
        'allocations' => [
            ['supplier_bill_id' => $bill->id, 'allocated_amount' => 800000],
        ],
    ]))->assertCreated()->json('data');

    $second = postJson('/api/ap-payments', apDraftPaymentPayload($this->supplier, $this->bankAccount, [
        'total_amount' => 800000,
        'allocations' => [
            ['supplier_bill_id' => $bill->id, 'allocated_amount' => 800000],
        ],
    ]))->assertCreated()->json('data');

    putJson('/api/ap-payments/' . $first['id'], [
        'status' => 'confirmed',
        'allocations' => [
            ['supplier_bill_id' => $bill->id, 'allocated_amount' => 800000],
        ],
    ])->assertOk();

    putJson('/api/ap-payments/' . $second['id'], ['status' => 'confirmed'])->assertStatus(422);

    $bill->refresh();
    expect((float) $bill->amount_paid)->toBe(800000.0)
        ->and(ApPayment::find($second['id'])->status)->toBe('draft');
});

test('put may resend an unchanged reconciled status and may reconcile a confirmed payment', function () {
    $bill = apDraftBill($this->supplier);

    $payment = postJson('/api/ap-payments', apDraftPaymentPayload($this->supplier, $this->bankAccount, [
        'allocations' => [
            ['supplier_bill_id' => $bill->id, 'allocated_amount' => 500000],
        ],
    ]))->assertCreated()->json('data');

    putJson('/api/ap-payments/' . $payment['id'], [
        'status' => 'confirmed',
        'allocations' => [
            ['supplier_bill_id' => $bill->id, 'allocated_amount' => 500000],
        ],
    ])->assertOk();

    putJson('/api/ap-payments/' . $payment['id'], ['status' => 'reconciled'])
        ->assertOk()->assertJsonPath('data.status', 'reconciled');

    putJson('/api/ap-payments/' . $payment['id'], [
        'status' => 'reconciled',
        'supplier_id' => $this->supplier->id,
    ])->assertOk()->assertJsonPath('data.status', 'reconciled');
});

test('voiding a confirmed payment restores bill due', function () {
    $bill = apDraftBill($this->supplier);

    $payment = postJson('/api/ap-payments', apDraftPaymentPayload($this->supplier, $this->bankAccount, [
        'allocations' => [
            ['supplier_bill_id' => $bill->id, 'allocated_amount' => 500000],
        ],
    ]))->assertCreated()->json('data');

    putJson('/api/ap-payments/' . $payment['id'], [
        'status' => 'confirmed',
        'allocations' => [
            ['supplier_bill_id' => $bill->id, 'allocated_amount' => 500000],
        ],
    ])->assertOk();

    putJson('/api/ap-payments/' . $payment['id'], ['status' => 'void'])->assertOk();

    $bill->refresh();
    expect((float) $bill->amount_paid)->toBe(0.0)
        ->and((float) $bill->amount_due)->toBe(1000000.0)
        ->and($bill->status)->toBe('confirmed');
});

test('deleting a draft payment leaves bill due unchanged', function () {
    $bill = apDraftBill($this->supplier);

    $payment = postJson('/api/ap-payments', apDraftPaymentPayload($this->supplier, $this->bankAccount, [
        'allocations' => [
            ['supplier_bill_id' => $bill->id, 'allocated_amount' => 500000],
        ],
    ]))->assertCreated()->json('data');

    deleteJson('/api/ap-payments/' . $payment['id'])->assertNoContent();

    $bill->refresh();
    expect((float) $bill->amount_paid)->toBe(0.0)
        ->and((float) $bill->amount_due)->toBe(1000000.0)
        ->and($bill->status)->toBe('confirmed');
});

test('moving a confirmed payment allocation off a bill is locked 422 and leaves allocation unchanged', function () {
    $billA = apDraftBill($this->supplier);
    $billB = apDraftBill($this->supplier);

    $payment = postJson('/api/ap-payments', apDraftPaymentPayload($this->supplier, $this->bankAccount, [
        'allocations' => [
            ['supplier_bill_id' => $billA->id, 'allocated_amount' => 500000],
        ],
    ]))->assertCreated()->json('data');

    putJson('/api/ap-payments/' . $payment['id'], [
        'status' => 'confirmed',
        'allocations' => [
            ['supplier_bill_id' => $billA->id, 'allocated_amount' => 500000],
        ],
    ])->assertOk();

    putJson('/api/ap-payments/' . $payment['id'], [
        'allocations' => [
            ['supplier_bill_id' => $billB->id, 'allocated_amount' => 500000],
        ],
    ])->assertStatus(422);

    $billA->refresh();
    $billB->refresh();
    expect((float) $billA->amount_paid)->toBe(500000.0)
        ->and((float) $billA->amount_due)->toBe(500000.0)
        ->and($billA->status)->toBe('partially_paid')
        ->and((float) $billB->amount_paid)->toBe(0.0)
        ->and((float) $billB->amount_due)->toBe(1000000.0)
        ->and($billB->status)->toBe('confirmed');
    expect(ApPaymentAllocation::query()
        ->where('ap_payment_id', $payment['id'])
        ->pluck('supplier_bill_id')
        ->all())->toBe([$billA->id]);
});
