<?php

use App\Models\Branch;
use App\Models\FiscalYear;
use App\Models\Supplier;
use App\Models\SupplierBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('ap-aging-report');

beforeEach(function () {
    $this->testUser = createTestUserWithPermissions(['ap_aging_report']);
    $this->otherUserAccount = createTestUserWithPermissions([]);
    Sanctum::actingAs($this->testUser, ['*']);
});

test('it requires permission to access ap aging report', function () {
    Sanctum::actingAs($this->otherUserAccount, ['*']);
    getJson('/api/reports/ap-aging')
        ->assertForbidden();
});

test('it can render ap aging report page', function () {
    SupplierBill::factory()->confirmed()->create();

    Sanctum::actingAs($this->testUser, ['*']);
    getJson('/api/reports/ap-aging')
        ->assertOk();
});

test('it can fetch ap aging data with aging buckets', function () {
    $supplier = Supplier::factory()->create(['name' => 'Supplier Aging']);
    $branch = Branch::factory()->create(['name' => 'Branch Aging']);
    $fiscalYear = FiscalYear::factory()->create();

    SupplierBill::factory()->confirmed()->create([
        'supplier_id' => $supplier->id,
        'branch_id' => $branch->id,
        'fiscal_year_id' => $fiscalYear->id,
        'due_date' => Carbon::now()->addDays(10)->format('Y-m-d'),
        'grand_total' => 1000000,
        'amount_due' => 1000000,
    ]);

    SupplierBill::factory()->confirmed()->create([
        'supplier_id' => $supplier->id,
        'branch_id' => $branch->id,
        'fiscal_year_id' => $fiscalYear->id,
        'due_date' => Carbon::now()->subDays(35)->format('Y-m-d'),
        'grand_total' => 2000000,
        'amount_due' => 2000000,
    ]);

    $response = getJson('/api/reports/ap-aging')
        ->assertOk();

    $response->assertJsonStructure([
        'data' => [
            '*' => [
                'id',
                'bill' => ['number', 'bill_date', 'due_date', 'status'],
                'supplier' => ['id', 'name'],
                'branch' => ['id', 'name'],
                'amounts' => ['grand_total', 'amount_paid', 'amount_due'],
                'aging_buckets' => ['current', 'days_1_30', 'days_31_60', 'days_61_90', 'days_over_90'],
            ],
        ],
    ]);

    $data = $response->json('data');
    expect($data)->toHaveCount(2);
});

test('it can filter by supplier', function () {
    $supplier1 = Supplier::factory()->create(['name' => 'Supplier A']);
    $supplier2 = Supplier::factory()->create(['name' => 'Supplier B']);

    SupplierBill::factory()->confirmed()->create([
        'supplier_id' => $supplier1->id,
        'due_date' => Carbon::now()->addDays(10)->format('Y-m-d'),
    ]);
    SupplierBill::factory()->confirmed()->create([
        'supplier_id' => $supplier2->id,
        'due_date' => Carbon::now()->addDays(10)->format('Y-m-d'),
    ]);

    $response = getJson('/api/reports/ap-aging?supplier_id=' . $supplier1->id)
        ->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['supplier']['id'])->toBe($supplier1->id);
});

test('it can export ap aging report', function () {
    Excel::fake();
    Storage::fake('public');
    SupplierBill::factory()->confirmed()->create();

    $response = postJson('/api/reports/ap-aging/export');

    $response->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');
    expect($filename)->toContain('ap_aging_report_');
    Excel::assertStored('exports/' . $filename, 'public');
});
