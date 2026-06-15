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

uses(RefreshDatabase::class)->group('ar-outstanding-report');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['ar_outstanding_report']);
    $this->otherUser = createTestUserWithPermissions([]);
});

test('it requires permission to access ar outstanding report', function () {
    Sanctum::actingAs($this->otherUser, ['*']);
    getJson('/api/reports/ar-outstanding')
        ->assertForbidden();
});

test('it can fetch ar outstanding report data', function () {
    $customer = Customer::factory()->create(['name' => 'Customer Outstanding']);
    $branch = Branch::factory()->create(['name' => 'Branch Outstanding']);
    $fiscalYear = FiscalYear::factory()->create();

    $invoice = CustomerInvoice::factory()->create([
        'invoice_number' => 'INV-OUT-001',
        'customer_id' => $customer->id,
        'branch_id' => $branch->id,
        'fiscal_year_id' => $fiscalYear->id,
        'invoice_date' => Carbon::now()->subDays(30),
        'due_date' => Carbon::now()->subDays(5),
        'status' => 'sent',
        'grand_total' => 150000,
    ]);

    Sanctum::actingAs($this->user, ['*']);
    getJson('/api/reports/ar-outstanding')
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data', 1)
            ->where('data.0.customer_invoice.invoice_number', 'INV-OUT-001')
            ->where('data.0.customer.name', 'Customer Outstanding')
            ->where('data.0.branch.name', 'Branch Outstanding')
            ->has('data.0.amounts')
            ->has('data.0.days_overdue')
            ->has('meta')
            ->has('links')
        );
});

test('it can filter by customer', function () {
    $customerA = Customer::factory()->create(['name' => 'Customer A']);
    $customerB = Customer::factory()->create(['name' => 'Customer B']);
    $fiscalYear = FiscalYear::factory()->create();

    CustomerInvoice::factory()->create([
        'invoice_number' => 'INV-OUT-A',
        'customer_id' => $customerA->id,
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'sent',
    ]);
    CustomerInvoice::factory()->create([
        'invoice_number' => 'INV-OUT-B',
        'customer_id' => $customerB->id,
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'sent',
    ]);

    Sanctum::actingAs($this->user, ['*']);

    getJson('/api/reports/ar-outstanding?customer_id=' . $customerA->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.customer_invoice.invoice_number', 'INV-OUT-A');
});

test('it can filter by status', function () {
    $fiscalYear = FiscalYear::factory()->create();

    CustomerInvoice::factory()->create([
        'invoice_number' => 'INV-SENT',
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'sent',
    ]);
    CustomerInvoice::factory()->create([
        'invoice_number' => 'INV-PARTIAL',
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'partially_paid',
    ]);

    Sanctum::actingAs($this->user, ['*']);

    getJson('/api/reports/ar-outstanding?status=partially_paid')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.customer_invoice.invoice_number', 'INV-PARTIAL');
});

test('it computes days_overdue in PHP without MariaDB-only DATEDIFF', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-04 10:00:00'));

    $customer = Customer::factory()->create();
    $fiscalYear = FiscalYear::factory()->create();

    CustomerInvoice::factory()->create([
        'customer_id' => $customer->id,
        'fiscal_year_id' => $fiscalYear->id,
        'invoice_number' => 'INV-OVERDUE',
        'due_date' => '2026-02-22',
        'status' => 'sent',
    ]);
    CustomerInvoice::factory()->create([
        'customer_id' => $customer->id,
        'fiscal_year_id' => $fiscalYear->id,
        'invoice_number' => 'INV-FUTURE',
        'due_date' => '2026-04-01',
        'status' => 'sent',
    ]);
    CustomerInvoice::factory()->create([
        'customer_id' => $customer->id,
        'fiscal_year_id' => $fiscalYear->id,
        'invoice_number' => 'INV-DRAFT-OVERDUE',
        'due_date' => '2026-01-01',
        'status' => 'draft',
    ]);

    Sanctum::actingAs($this->user, ['*']);
    $response = getJson('/api/reports/ar-outstanding?sort_by=invoice_number&sort_direction=asc')
        ->assertOk();

    $data = $response->json('data');
    $byNumber = collect($data)->keyBy('customer_invoice.invoice_number');

    expect($byNumber['INV-OVERDUE']['days_overdue'])->toBe(10);
    expect($byNumber['INV-FUTURE']['days_overdue'])->toBe(0);
    // Draft invoices appear in result set but days_overdue is zero (status-gated in PHP).
    expect($byNumber['INV-DRAFT-OVERDUE']['days_overdue'])->toBe(0);

    Carbon::setTestNow();
});

test('it sorts by days_overdue using due_date ascending', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-04 10:00:00'));

    $customer = Customer::factory()->create();
    $fiscalYear = FiscalYear::factory()->create();

    CustomerInvoice::factory()->create([
        'customer_id' => $customer->id,
        'fiscal_year_id' => $fiscalYear->id,
        'invoice_number' => 'INV-OLD',
        'due_date' => '2026-01-10',
        'status' => 'sent',
    ]);
    CustomerInvoice::factory()->create([
        'customer_id' => $customer->id,
        'fiscal_year_id' => $fiscalYear->id,
        'invoice_number' => 'INV-RECENT',
        'due_date' => '2026-02-25',
        'status' => 'sent',
    ]);

    Sanctum::actingAs($this->user, ['*']);
    $response = getJson('/api/reports/ar-outstanding?sort_by=days_overdue')
        ->assertOk();

    $invoiceNumbers = collect($response->json('data'))
        ->pluck('customer_invoice.invoice_number')
        ->all();

    expect($invoiceNumbers[0])->toBe('INV-OLD');
    expect($invoiceNumbers[1])->toBe('INV-RECENT');

    Carbon::setTestNow();
});

test('it can export ar outstanding report', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-04 10:00:00'));
    Excel::fake();
    Storage::fake('public');

    Sanctum::actingAs($this->user, ['*']);
    $response = postJson('/api/reports/ar-outstanding/export')
        ->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');
    expect($filename)->toStartWith('ar_outstanding_2026-03-04_10-00-00_');
    expect($filename)->toEndWith('.xlsx');

    Excel::assertStored('exports/' . $filename, 'public');
    Carbon::setTestNow();
});
