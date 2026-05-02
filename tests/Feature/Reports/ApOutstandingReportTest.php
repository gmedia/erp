<?php

use App\Models\Supplier;
use App\Models\SupplierBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('ap-outstanding-report');

beforeEach(function () {
    $this->testUser = createTestUserWithPermissions(['ap_outstanding_report']);
    $this->otherUserAccount = createTestUserWithPermissions([]);
    Sanctum::actingAs($this->testUser, ['*']);
});

test('it requires permission to access ap outstanding report', function () {
    Sanctum::actingAs($this->otherUserAccount, ['*']);
    getJson('/api/reports/ap-outstanding')
        ->assertForbidden();
});

test('it can render ap outstanding report page', function () {
    SupplierBill::factory()->confirmed()->create();

    Sanctum::actingAs($this->testUser, ['*']);
    getJson('/api/reports/ap-outstanding')
        ->assertOk();
});

test('it returns only unpaid bills', function () {
    $supplier = Supplier::factory()->create(['name' => 'Supplier Outstanding']);

    SupplierBill::factory()->create([
        'supplier_id' => $supplier->id,
        'status' => 'draft',
        'grand_total' => 1000000,
        'amount_due' => 1000000,
    ]);

    $confirmedBill = SupplierBill::factory()->confirmed()->create([
        'supplier_id' => $supplier->id,
        'grand_total' => 2000000,
        'amount_due' => 2000000,
    ]);

    SupplierBill::factory()->paid()->create([
        'supplier_id' => $supplier->id,
        'grand_total' => 3000000,
        'amount_due' => 0,
    ]);

    $response = getJson('/api/reports/ap-outstanding')
        ->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['id'])->toBe($confirmedBill->id);
    expect($data[0]['amounts']['amount_due'])->toBe('2000000.00');
});

test('it can filter by supplier and due date range', function () {
    $supplier1 = Supplier::factory()->create(['name' => 'Supplier A']);
    $supplier2 = Supplier::factory()->create(['name' => 'Supplier B']);

    SupplierBill::factory()->confirmed()->create([
        'supplier_id' => $supplier1->id,
        'due_date' => Carbon::now()->addDays(10)->format('Y-m-d'),
    ]);
    SupplierBill::factory()->confirmed()->create([
        'supplier_id' => $supplier2->id,
        'due_date' => Carbon::now()->addDays(20)->format('Y-m-d'),
    ]);

    $response = getJson('/api/reports/ap-outstanding?supplier_id=' . $supplier1->id)
        ->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['supplier']['id'])->toBe($supplier1->id);

    $fromDate = Carbon::now()->subDays(1)->format('Y-m-d');
    $toDate = Carbon::now()->addDays(15)->format('Y-m-d');
    $response = getJson("/api/reports/ap-outstanding?due_date_from={$fromDate}&due_date_to={$toDate}")
        ->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
});

test('it can export ap outstanding report', function () {
    Excel::fake();
    Storage::fake('public');
    SupplierBill::factory()->confirmed()->create();

    $response = postJson('/api/reports/ap-outstanding/export');

    $response->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');
    expect($filename)->toContain('ap_outstanding_report_');
    Excel::assertStored('exports/' . $filename, 'public');
});
