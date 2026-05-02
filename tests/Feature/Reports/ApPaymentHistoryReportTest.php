<?php

use App\Models\Account;
use App\Models\ApPayment;
use App\Models\Branch;
use App\Models\FiscalYear;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('ap-payment-history-report');

beforeEach(function () {
    $this->testUser = createTestUserWithPermissions(['ap_payment_history_report']);
    $this->otherUserAccount = createTestUserWithPermissions([]);
    Sanctum::actingAs($this->testUser, ['*']);
});

test('it requires permission to access ap payment history report', function () {
    Sanctum::actingAs($this->otherUserAccount, ['*']);
    getJson('/api/reports/ap-payment-history')
        ->assertForbidden();
});

test('it can render ap payment history report page', function () {
    ApPayment::factory()->confirmed()->create();

    Sanctum::actingAs($this->testUser, ['*']);
    getJson('/api/reports/ap-payment-history')
        ->assertOk();
});

test('it returns confirmed payments with details', function () {
    $supplier = Supplier::factory()->create(['name' => 'Supplier Payment History']);
    $branch = Branch::factory()->create(['name' => 'Branch Payment History']);
    $fiscalYear = FiscalYear::factory()->create();
    $bankAccount = Account::factory()->create(['type' => 'asset']);

    $apPayment = ApPayment::factory()->confirmed()->create([
        'supplier_id' => $supplier->id,
        'branch_id' => $branch->id,
        'fiscal_year_id' => $fiscalYear->id,
        'bank_account_id' => $bankAccount->id,
        'payment_method' => 'bank_transfer',
        'total_amount' => 1000000,
        'total_allocated' => 1000000,
        'total_unallocated' => 0,
    ]);

    $response = getJson('/api/reports/ap-payment-history')
        ->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['id'])->toBe($apPayment->id);
    expect($data[0]['supplier']['name'])->toBe('Supplier Payment History');
    expect($data[0]['payment']['method'])->toBe('bank_transfer');
    expect($data[0]['amounts']['total'])->toBe('1000000.00');
});

test('it can filter by supplier and payment method', function () {
    $supplier1 = Supplier::factory()->create(['name' => 'Supplier A']);
    $supplier2 = Supplier::factory()->create(['name' => 'Supplier B']);

    ApPayment::factory()->confirmed()->create([
        'supplier_id' => $supplier1->id,
        'payment_method' => 'bank_transfer',
    ]);
    ApPayment::factory()->confirmed()->create([
        'supplier_id' => $supplier2->id,
        'payment_method' => 'cash',
    ]);

    $response = getJson('/api/reports/ap-payment-history?supplier_id=' . $supplier1->id)
        ->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['supplier']['id'])->toBe($supplier1->id);

    $response = getJson('/api/reports/ap-payment-history?payment_method=bank_transfer')
        ->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['payment']['method'])->toBe('bank_transfer');
});

test('it can export ap payment history report', function () {
    Excel::fake();
    Storage::fake('public');
    ApPayment::factory()->confirmed()->create();

    $response = postJson('/api/reports/ap-payment-history/export');

    $response->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');
    expect($filename)->toContain('ap_payment_history_report_');
    Excel::assertStored('exports/' . $filename, 'public');
});
