<?php

use App\Actions\AccountingPosting\PostApPaymentJournalAction;
use App\Models\Account;
use App\Models\ApPayment;
use App\Models\Branch;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\Supplier;
use App\Models\SupplierBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
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

test('index returns paginated ap payments', function () {
    ApPayment::factory()->count(20)->create();

    $response = getJson('/api/ap-payments?per_page=10');

    $response->assertOk()
        ->assertJsonStructure([
            'data',
            'meta' => ['total', 'per_page', 'current_page'],
        ]);

    expect($response->json('data'))->toHaveCount(10);
});

test('index supports search and filters', function () {
    $supplier = Supplier::factory()->create();
    $branch = Branch::factory()->create();
    $fiscalYear = FiscalYear::factory()->create();

    ApPayment::factory()->create([
        'payment_number' => 'PAY-SEARCH-001',
        'supplier_id' => $supplier->id,
        'branch_id' => $branch->id,
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'draft',
        'payment_method' => 'bank_transfer',
    ]);
    ApPayment::factory()->create([
        'status' => 'confirmed',
        'payment_method' => 'cash',
    ]);

    getJson('/api/ap-payments?search=PAY-SEARCH-001')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    getJson('/api/ap-payments?supplier_id=' . $supplier->id)
        ->assertOk()
        ->assertJsonCount(1, 'data');

    getJson('/api/ap-payments?branch_id=' . $branch->id)
        ->assertOk()
        ->assertJsonCount(1, 'data');

    getJson('/api/ap-payments?fiscal_year_id=' . $fiscalYear->id)
        ->assertOk()
        ->assertJsonCount(1, 'data');

    getJson('/api/ap-payments?status=draft&payment_method=bank_transfer')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('store creates ap payment with allocations', function () {
    $supplier = Supplier::factory()->create();
    $branch = Branch::factory()->create();
    $fiscalYear = FiscalYear::factory()->create();
    $bankAccount = Account::factory()->create(['type' => 'asset']);
    $supplierBill = SupplierBill::factory()->confirmed()->create();

    $allocationAmount = (int) $supplierBill->grand_total;

    $payload = [
        'supplier_id' => $supplier->id,
        'branch_id' => $branch->id,
        'fiscal_year_id' => $fiscalYear->id,
        'payment_date' => '2026-03-05',
        'payment_method' => 'bank_transfer',
        'bank_account_id' => $bankAccount->id,
        'currency' => 'IDR',
        'total_amount' => $allocationAmount,
        'reference' => 'TRF-001',
        'status' => 'draft',
        'notes' => 'Initial payment',
        'allocations' => [
            [
                'supplier_bill_id' => $supplierBill->id,
                'allocated_amount' => $allocationAmount,
                'discount_taken' => 0,
                'notes' => 'Full payment',
            ],
        ],
    ];

    $response = postJson('/api/ap-payments', $payload);

    $response->assertCreated()
        ->assertJsonPath('data.currency', 'IDR')
        ->assertJsonPath('data.allocations.0.supplier_bill_id', $supplierBill->id);

    $id = $response->json('data.id');
    assertDatabaseHas('ap_payments', ['id' => $id, 'supplier_id' => $supplier->id]);
    assertDatabaseHas('ap_payment_allocations', ['ap_payment_id' => $id, 'supplier_bill_id' => $supplierBill->id]);
});

test('show returns ap payment detail', function () {
    $apPayment = ApPayment::factory()->create();
    $supplierBill = SupplierBill::factory()->confirmed()->create();
    $apPayment->allocations()->create([
        'supplier_bill_id' => $supplierBill->id,
        'allocated_amount' => 500000,
        'discount_taken' => 0,
    ]);

    getJson('/api/ap-payments/' . $apPayment->id)
        ->assertOk()
        ->assertJsonPath('data.id', $apPayment->id)
        ->assertJsonCount(1, 'data.allocations');
});

test('update modifies ap payment', function () {
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
    $bankAccount = Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '11110',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);

    $apPayment = ApPayment::factory()->create([
        'payment_date' => '2026-03-15',
        'bank_account_id' => $bankAccount->id,
        'total_amount' => 500000,
    ]);

    $payload = [
        'status' => 'confirmed',
    ];

    putJson('/api/ap-payments/' . $apPayment->id, $payload)
        ->assertOk()
        ->assertJsonPath('data.status', 'confirmed');
});

test('destroy removes ap payment', function () {
    $apPayment = ApPayment::factory()->create();

    deleteJson('/api/ap-payments/' . $apPayment->id)
        ->assertNoContent();

    assertDatabaseMissing('ap_payments', ['id' => $apPayment->id]);
});

test('confirming ap payment rolls back state when journal posting fails', function () {
    $apPayment = ApPayment::factory()->create([
        'status' => 'draft',
        'confirmed_at' => null,
        'confirmed_by' => null,
    ]);

    $this->app->bind(PostApPaymentJournalAction::class, function () {
        return new class
        {
            public function execute(ApPayment $apPayment): void
            {
                throw new RuntimeException('Simulated journal posting failure');
            }
        };
    });

    putJson('/api/ap-payments/' . $apPayment->id, ['status' => 'confirmed'])
        ->assertStatus(500);

    $apPayment->refresh();
    expect($apPayment->status)->toBe('draft');
    expect($apPayment->confirmed_at)->toBeNull();
    expect($apPayment->confirmed_by)->toBeNull();
});
