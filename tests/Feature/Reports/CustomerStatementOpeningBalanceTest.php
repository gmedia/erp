<?php

use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class)->group('customer-statement-report');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['customer_statement_report']);
    Sanctum::actingAs($this->user, ['*']);
});

test('customer statement running balance includes receivables before start_date as opening balance', function () {
    $customer = Customer::factory()->create();
    $fiscalYear = FiscalYear::factory()->create(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);

    // Piutang sebelum periode laporan (saldo awal).
    CustomerInvoice::factory()->create([
        'customer_id' => $customer->id,
        'fiscal_year_id' => $fiscalYear->id,
        'invoice_number' => 'INV-OPEN-001',
        'invoice_date' => '2026-01-10',
        'due_date' => '2026-02-10',
        'status' => 'sent',
        'grand_total' => 100000,
        'amount_received' => 0,
        'amount_due' => 100000,
    ]);

    // Piutang dalam periode laporan.
    CustomerInvoice::factory()->create([
        'customer_id' => $customer->id,
        'fiscal_year_id' => $fiscalYear->id,
        'invoice_number' => 'INV-IN-001',
        'invoice_date' => '2026-02-15',
        'due_date' => '2026-03-15',
        'status' => 'sent',
        'grand_total' => 50000,
        'amount_received' => 0,
        'amount_due' => 50000,
    ]);

    $response = getJson("/api/reports/customer-statement?customer_id={$customer->id}&start_date=2026-02-01&end_date=2026-02-28")
        ->assertOk();

    expect(collect($response->json('data'))->pluck('customer_invoice.invoice_number')->all())->toBe(['INV-IN-001']);

    // Saldo berjalan harus mencakup saldo awal 100000 + 50000.
    expect((float) $response->json('data.0.running_balance'))->toBe(150000.0);
});

test('customer statement filters by branch_id', function () {
    $customer = Customer::factory()->create();
    $fiscalYear = FiscalYear::factory()->create();
    $branchA = Branch::factory()->create();
    $branchB = Branch::factory()->create();

    foreach ([[$branchA, 'INV-BR-A'], [$branchB, 'INV-BR-B']] as [$branch, $number]) {
        CustomerInvoice::factory()->create([
            'customer_id' => $customer->id,
            'fiscal_year_id' => $fiscalYear->id,
            'branch_id' => $branch->id,
            'invoice_number' => $number,
            'invoice_date' => '2026-01-10',
            'status' => 'sent',
            'grand_total' => 100000,
            'amount_received' => 0,
            'amount_due' => 100000,
        ]);
    }

    $numbers = collect(
        getJson("/api/reports/customer-statement?customer_id={$customer->id}&branch_id={$branchA->id}")
            ->assertOk()
            ->json('data'),
    )->pluck('customer_invoice.invoice_number')->all();

    expect($numbers)->toBe(['INV-BR-A']);
});
