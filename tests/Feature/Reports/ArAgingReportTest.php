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
        );
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
