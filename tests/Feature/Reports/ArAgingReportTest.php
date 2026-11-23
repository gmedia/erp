<?php

use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('ar-aging-report');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['ar_aging_report']);
    $this->otherUser = createTestUserWithPermissions([]);
});

test('it requires permission to access ar aging report', function () {
    Sanctum::actingAs($this->otherUser, ['*']);
    getJson('/api/reports/ar-aging')
        ->assertForbidden();
});

test('it can fetch ar aging report data with aging buckets', function () {
    $customer = Customer::factory()->create(['name' => 'Customer AR']);
    $branch = Branch::factory()->create(['name' => 'Branch AR']);
    $fiscalYear = FiscalYear::factory()->create();

    $invoice = CustomerInvoice::factory()->create([
        'invoice_number' => 'INV-AR-001',
        'customer_id' => $customer->id,
        'branch_id' => $branch->id,
        'fiscal_year_id' => $fiscalYear->id,
        'invoice_date' => Carbon::now()->subDays(45),
        'due_date' => Carbon::now()->subDays(15),
        'status' => 'sent',
        'grand_total' => 100000,
    ]);

    Sanctum::actingAs($this->user, ['*']);
    getJson('/api/reports/ar-aging')
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data', 1)
            ->where('data.0.customer_invoice.invoice_number', 'INV-AR-001')
            ->where('data.0.customer.name', 'Customer AR')
            ->where('data.0.branch.name', 'Branch AR')
            ->has('data.0.amounts')
            ->has('data.0.aging_buckets')
            ->has('meta')
            ->has('links')
            ->has('summary')
        );
});

test('it excludes paid cancelled void and draft invoices', function () {
    $customer = Customer::factory()->create();
    $fiscalYear = FiscalYear::factory()->create();

    foreach (['sent', 'paid', 'cancelled', 'void', 'draft'] as $status) {
        CustomerInvoice::factory()->create([
            'customer_id' => $customer->id,
            'fiscal_year_id' => $fiscalYear->id,
            'invoice_number' => 'INV-AGING-' . strtoupper($status),
            'invoice_date' => '2026-01-10',
            'due_date' => '2026-02-10',
            'status' => $status,
            'grand_total' => 100000,
            'amount_received' => $status === 'paid' ? 100000 : 0,
            'amount_due' => $status === 'paid' ? 0 : 100000,
        ]);
    }

    Sanctum::actingAs($this->user, ['*']);
    $numbers = collect(getJson('/api/reports/ar-aging')->assertOk()->json('data'))
        ->pluck('customer_invoice.invoice_number')
        ->all();

    expect($numbers)->toContain('INV-AGING-SENT')
        ->not->toContain('INV-AGING-PAID')
        ->not->toContain('INV-AGING-CANCELLED')
        ->not->toContain('INV-AGING-VOID')
        ->not->toContain('INV-AGING-DRAFT');
});

test('it can filter by customer', function () {
    $customerA = Customer::factory()->create(['name' => 'Customer A']);
    $customerB = Customer::factory()->create(['name' => 'Customer B']);
    $fiscalYear = FiscalYear::factory()->create();

    CustomerInvoice::factory()->create([
        'invoice_number' => 'INV-A-001',
        'customer_id' => $customerA->id,
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'sent',
    ]);
    CustomerInvoice::factory()->create([
        'invoice_number' => 'INV-B-001',
        'customer_id' => $customerB->id,
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'sent',
    ]);

    Sanctum::actingAs($this->user, ['*']);

    getJson('/api/reports/ar-aging?customer_id=' . $customerA->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.customer_invoice.invoice_number', 'INV-A-001');
});

test('it can filter by branch', function () {
    $branchA = Branch::factory()->create(['name' => 'Branch A']);
    $branchB = Branch::factory()->create(['name' => 'Branch B']);
    $fiscalYear = FiscalYear::factory()->create();

    CustomerInvoice::factory()->create([
        'invoice_number' => 'INV-BR-A',
        'branch_id' => $branchA->id,
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'sent',
    ]);
    CustomerInvoice::factory()->create([
        'invoice_number' => 'INV-BR-B',
        'branch_id' => $branchB->id,
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'sent',
    ]);

    Sanctum::actingAs($this->user, ['*']);

    getJson('/api/reports/ar-aging?branch_id=' . $branchB->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.customer_invoice.invoice_number', 'INV-BR-B');
});

test('it can export ar aging report', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-04 10:00:00'));
    Excel::fake();
    Storage::fake('public');

    Sanctum::actingAs($this->user, ['*']);
    $response = postJson('/api/reports/ar-aging/export')
        ->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');
    expect($filename)->toStartWith('ar_aging_2026-03-04_10-00-00_');
    expect($filename)->toEndWith('.xlsx');

    Excel::assertStored('exports/' . $filename, 'public');
    Carbon::setTestNow();
});

test('it returns totals across all filtered rows not only the current page', function () {
    $customer = Customer::factory()->create();
    $fiscalYear = FiscalYear::factory()->create();

    foreach ([1, 2] as $i) {
        CustomerInvoice::factory()->create([
            'invoice_number' => 'INV-TOTAL-' . $i,
            'customer_id' => $customer->id,
            'fiscal_year_id' => $fiscalYear->id,
            'invoice_date' => '2026-01-10',
            'due_date' => '2026-02-10',
            'status' => 'sent',
            'grand_total' => 100000 * $i,
            'amount_received' => 0,
            'amount_due' => 100000 * $i,
        ]);
    }

    Sanctum::actingAs($this->user, ['*']);
    getJson('/api/reports/ar-aging?per_page=1')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('summary.totals.amount_due', '300000.00')
        ->assertJsonPath('summary.totals.grand_total', '300000.00')
        ->assertJsonPath('summary.as_of_date', Carbon::today()->toDateString());
});

test('it rejects mixed currencies in the aggregated scope', function () {
    Sanctum::actingAs($this->user, ['*']);

    CustomerInvoice::factory()->create([
        'currency' => 'IDR',
        'due_date' => Carbon::now()->addDays(10)->format('Y-m-d'),
    ]);
    CustomerInvoice::factory()->create([
        'currency' => 'USD',
        'due_date' => Carbon::now()->subDays(35)->format('Y-m-d'),
    ]);

    getJson('/api/reports/ar-aging')
        ->assertStatus(422)
        ->assertJsonValidationErrors('currency');
});
