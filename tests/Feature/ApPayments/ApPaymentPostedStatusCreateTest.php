<?php

use App\Models\Account;
use App\Models\Branch;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\Supplier;
use App\Models\SupplierBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('ap-payments');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'ap_payment',
        'ap_payment.create',
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

test('creating an ap payment with a payment-result status is rejected with 422', function (string $status) {
    $bill = SupplierBill::factory()->create([
        'supplier_id' => $this->supplier->id,
        'grand_total' => 1000000,
        'amount_paid' => 0,
        'amount_due' => 1000000,
        'status' => 'confirmed',
    ]);

    postJson('/api/ap-payments', [
        'supplier_id' => $this->supplier->id,
        'branch_id' => $this->branch->id,
        'fiscal_year_id' => $this->fiscalYear->id,
        'payment_date' => '2026-03-06',
        'payment_method' => 'bank_transfer',
        'bank_account_id' => $this->bankAccount->id,
        'currency' => 'IDR',
        'total_amount' => 500000,
        'status' => $status,
        'allocations' => [
            ['supplier_bill_id' => $bill->id, 'allocated_amount' => 500000],
        ],
    ])->assertStatus(422)->assertJsonValidationErrors(['status']);

    $bill->refresh();
    expect((float) $bill->amount_paid)->toBe(0.0)
        ->and($bill->status)->toBe('confirmed');
})->with(['reconciled']);
