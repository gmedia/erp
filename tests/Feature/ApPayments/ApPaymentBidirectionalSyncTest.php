<?php

use App\Models\Account;
use App\Models\ApPayment;
use App\Models\Branch;
use App\Models\FiscalYear;
use App\Models\Supplier;
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
});

test('creating payment updates supplier bill amount_paid and amount_due', function () {
    $supplier = Supplier::factory()->create();
    $branch = Branch::factory()->create();
    $fiscalYear = FiscalYear::factory()->create();
    $bankAccount = Account::factory()->create(['type' => 'asset']);
    $supplierBill = SupplierBill::factory()->confirmed()->create([
        'grand_total' => 1000000,
        'amount_paid' => 0,
        'amount_due' => 1000000,
        'status' => 'confirmed',
    ]);

    $payload = [
        'supplier_id' => $supplier->id,
        'branch_id' => $branch->id,
        'fiscal_year_id' => $fiscalYear->id,
        'payment_date' => '2026-03-05',
        'payment_method' => 'bank_transfer',
        'bank_account_id' => $bankAccount->id,
        'currency' => 'IDR',
        'total_amount' => 500000,
        'status' => 'draft',
        'allocations' => [
            [
                'supplier_bill_id' => $supplierBill->id,
                'allocated_amount' => 500000,
                'discount_taken' => 0,
            ],
        ],
    ];

    postJson('/api/ap-payments', $payload)->assertCreated();

    $supplierBill->refresh();
    expect($supplierBill->amount_paid)->toBe('500000.00');
    expect($supplierBill->amount_due)->toBe('500000.00');
    expect($supplierBill->status)->toBe('partially_paid');
});

test('updating payment allocation updates supplier bill amounts', function () {
    $supplier = Supplier::factory()->create();
    $branch = Branch::factory()->create();
    $fiscalYear = FiscalYear::factory()->create();
    $bankAccount = Account::factory()->create(['type' => 'asset']);
    $supplierBill = SupplierBill::factory()->confirmed()->create([
        'grand_total' => 1000000,
        'amount_paid' => 0,
        'amount_due' => 1000000,
        'status' => 'confirmed',
    ]);

    $apPayment = ApPayment::factory()->create([
        'supplier_id' => $supplier->id,
        'branch_id' => $branch->id,
        'fiscal_year_id' => $fiscalYear->id,
        'bank_account_id' => $bankAccount->id,
        'total_amount' => 500000,
        'total_allocated' => 500000,
        'total_unallocated' => 0,
    ]);

    $apPayment->allocations()->create([
        'supplier_bill_id' => $supplierBill->id,
        'allocated_amount' => 500000,
        'discount_taken' => 0,
    ]);

    $supplierBill->update([
        'amount_paid' => 500000,
        'amount_due' => 500000,
        'status' => 'partially_paid',
    ]);

    $payload = [
        'allocations' => [
            [
                'supplier_bill_id' => $supplierBill->id,
                'allocated_amount' => 1000000,
                'discount_taken' => 0,
            ],
        ],
    ];

    putJson('/api/ap-payments/' . $apPayment->id, $payload)->assertOk();

    $supplierBill->refresh();
    expect($supplierBill->amount_paid)->toBe('1000000.00');
    expect($supplierBill->amount_due)->toBe('0.00');
    expect($supplierBill->status)->toBe('paid');
});

test('deleting payment reverts supplier bill amounts', function () {
    $supplier = Supplier::factory()->create();
    $branch = Branch::factory()->create();
    $fiscalYear = FiscalYear::factory()->create();
    $bankAccount = Account::factory()->create(['type' => 'asset']);
    $supplierBill = SupplierBill::factory()->confirmed()->create([
        'grand_total' => 1000000,
        'amount_paid' => 500000,
        'amount_due' => 500000,
        'status' => 'partially_paid',
    ]);

    $apPayment = ApPayment::factory()->create([
        'supplier_id' => $supplier->id,
        'branch_id' => $branch->id,
        'fiscal_year_id' => $fiscalYear->id,
        'bank_account_id' => $bankAccount->id,
        'total_amount' => 500000,
        'total_allocated' => 500000,
        'total_unallocated' => 0,
    ]);

    $apPayment->allocations()->create([
        'supplier_bill_id' => $supplierBill->id,
        'allocated_amount' => 500000,
        'discount_taken' => 0,
    ]);

    deleteJson('/api/ap-payments/' . $apPayment->id)->assertNoContent();

    $supplierBill->refresh();
    expect($supplierBill->amount_paid)->toBe('0.00');
    expect($supplierBill->amount_due)->toBe('1000000.00');
    expect($supplierBill->status)->toBe('confirmed');
});

test('over-allocation is prevented', function () {
    $supplier = Supplier::factory()->create();
    $branch = Branch::factory()->create();
    $fiscalYear = FiscalYear::factory()->create();
    $bankAccount = Account::factory()->create(['type' => 'asset']);
    $supplierBill = SupplierBill::factory()->confirmed()->create([
        'grand_total' => 1000000,
        'amount_paid' => 0,
        'amount_due' => 1000000,
    ]);

    $payload = [
        'supplier_id' => $supplier->id,
        'branch_id' => $branch->id,
        'fiscal_year_id' => $fiscalYear->id,
        'payment_date' => '2026-03-05',
        'payment_method' => 'bank_transfer',
        'bank_account_id' => $bankAccount->id,
        'currency' => 'IDR',
        'total_amount' => 1500000,
        'status' => 'draft',
        'allocations' => [
            [
                'supplier_bill_id' => $supplierBill->id,
                'allocated_amount' => 1500000,
                'discount_taken' => 0,
            ],
        ],
    ];

    postJson('/api/ap-payments', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['allocations.0.allocated_amount']);
});
