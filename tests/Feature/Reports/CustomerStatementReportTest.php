<?php

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

uses(RefreshDatabase::class)->group('customer-statement-report');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['customer_statement_report']);
    $this->otherUser = createTestUserWithPermissions([]);
});

test('it requires permission to access customer statement report', function () {
    Sanctum::actingAs($this->otherUser, ['*']);
    getJson('/api/reports/customer-statement')
        ->assertForbidden();
});

test('it can fetch customer statement report data', function () {
    $customer = Customer::factory()->create(['name' => 'Customer Statement']);
    $fiscalYear = FiscalYear::factory()->create();

    $invoice = CustomerInvoice::factory()->create([
        'invoice_number' => 'INV-STMT-001',
        'customer_id' => $customer->id,
        'fiscal_year_id' => $fiscalYear->id,
        'invoice_date' => Carbon::now()->subDays(10),
        'status' => 'sent',
        'grand_total' => 200000,
    ]);

    Sanctum::actingAs($this->user, ['*']);
    getJson('/api/reports/customer-statement?customer_id=' . $customer->id)
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data', 1)
            ->where('data.0.customer_invoice.invoice_number', 'INV-STMT-001')
            ->where('data.0.customer.name', 'Customer Statement')
            ->has('data.0.amounts')
            ->has('data.0.running_balance')
            ->has('meta')
            ->has('links')
        );
});

test('it can filter by customer', function () {
    $customerA = Customer::factory()->create(['name' => 'Customer A']);
    $customerB = Customer::factory()->create(['name' => 'Customer B']);
    $fiscalYear = FiscalYear::factory()->create();

    CustomerInvoice::factory()->create([
        'invoice_number' => 'INV-STMT-A',
        'customer_id' => $customerA->id,
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'sent',
    ]);
    CustomerInvoice::factory()->create([
        'invoice_number' => 'INV-STMT-B',
        'customer_id' => $customerB->id,
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'sent',
    ]);

    Sanctum::actingAs($this->user, ['*']);

    getJson('/api/reports/customer-statement?customer_id=' . $customerA->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.customer_invoice.invoice_number', 'INV-STMT-A');
});

test('it can filter by date range', function () {
    $customer = Customer::factory()->create();
    $fiscalYear = FiscalYear::factory()->create();

    CustomerInvoice::factory()->create([
        'invoice_number' => 'INV-OLD',
        'customer_id' => $customer->id,
        'fiscal_year_id' => $fiscalYear->id,
        'invoice_date' => '2026-01-15',
        'status' => 'sent',
    ]);
    CustomerInvoice::factory()->create([
        'invoice_number' => 'INV-NEW',
        'customer_id' => $customer->id,
        'fiscal_year_id' => $fiscalYear->id,
        'invoice_date' => '2026-03-15',
        'status' => 'sent',
    ]);

    Sanctum::actingAs($this->user, ['*']);

    getJson('/api/reports/customer-statement?customer_id=' . $customer->id . '&start_date=2026-03-01&end_date=2026-03-31')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.customer_invoice.invoice_number', 'INV-NEW');
});

test('it can export customer statement report', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-04 10:00:00'));
    Excel::fake();
    Storage::fake('public');

    Sanctum::actingAs($this->user, ['*']);
    $response = postJson('/api/reports/customer-statement/export')
        ->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');
    expect($filename)->toStartWith('customer_statement_report_2026-03-04_10-00-00_');
    expect($filename)->toEndWith('.xlsx');

    Excel::assertStored('exports/' . $filename, 'public');
    Carbon::setTestNow();
});
