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

test('put status transition for a supplier bill follows the whitelist', function (
    string $from,
    string $to,
    int $expected,
) {
    $bill = SupplierBill::factory()->create(['status' => $from]);

    putJson('/api/supplier-bills/' . $bill->id, [
        'status' => $to,
    ])->assertStatus($expected);

    if ($expected === 422) {
        expect($bill->refresh()->status)->toBe($from);
    }
})->with([
    'draft to partially_paid is rejected' => ['draft', 'partially_paid', 422],
    'draft to paid is rejected' => ['draft', 'paid', 422],
    'confirmed to paid is rejected' => ['confirmed', 'paid', 422],
    'confirmed to partially_paid is rejected' => ['confirmed', 'partially_paid', 422],
    'partially_paid resent unchanged is allowed' => ['partially_paid', 'partially_paid', 200],
    'paid resent unchanged is allowed' => ['paid', 'paid', 200],
    'confirmed to overdue is allowed' => ['confirmed', 'overdue', 200],
]);

test('supplier bill status rejection message is translated for en and id', function (string $locale, string $expected) {
    $bill = SupplierBill::factory()->create(['status' => 'draft']);

    $response = putJson('/api/supplier-bills/' . $bill->id, [
        'status' => 'paid',
    ], ['X-Locale' => $locale])->assertStatus(422);

    expect($response->json('errors.status.0'))->toBe($expected);
})->with([
    ['en', 'Cannot change status from draft to paid.'],
    ['id', 'Tidak dapat mengubah status dari draft ke paid.'],
]);
