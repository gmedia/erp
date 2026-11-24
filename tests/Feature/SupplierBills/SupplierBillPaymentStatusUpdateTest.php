<?php

use App\Models\SupplierBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('supplier-bills');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'supplier_bill',
        'supplier_bill.edit',
    ]);
    Sanctum::actingAs($user, ['*']);
});

test('put may resend the stored payment-result status unchanged', function (string $status) {
    $bill = SupplierBill::factory()->create(['status' => $status]);

    putJson('/api/supplier-bills/' . $bill->id, [
        'status' => $status,
    ])->assertOk()->assertJsonPath('data.status', $status);

    expect($bill->refresh()->status)->toBe($status);
})->with(['partially_paid', 'paid', 'overdue']);

test('put may mark a confirmed supplier bill overdue', function () {
    $bill = SupplierBill::factory()->create(['status' => 'confirmed']);

    putJson('/api/supplier-bills/' . $bill->id, [
        'status' => 'overdue',
    ])->assertOk()->assertJsonPath('data.status', 'overdue');
});

test('put may not move a draft supplier bill straight to overdue', function () {
    $bill = SupplierBill::factory()->create(['status' => 'draft']);

    putJson('/api/supplier-bills/' . $bill->id, [
        'status' => 'overdue',
    ])->assertStatus(422)->assertJsonValidationErrors(['status']);

    expect($bill->refresh()->status)->toBe('draft');
});
