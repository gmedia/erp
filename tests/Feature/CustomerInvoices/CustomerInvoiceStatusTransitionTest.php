<?php

use App\Models\CustomerInvoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('customer-invoices');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'customer_invoice',
        'customer_invoice.edit',
    ]);
    Sanctum::actingAs($user, ['*']);
});

test('put status transition for a customer invoice follows the whitelist', function (
    string $from,
    string $to,
    int $expected,
) {
    $invoice = CustomerInvoice::factory()->create(['status' => $from]);

    putJson('/api/customer-invoices/' . $invoice->id, [
        'status' => $to,
    ])->assertStatus($expected);

    if ($expected === 422) {
        expect($invoice->refresh()->status)->toBe($from);
    }
})->with([
    'draft to partially_paid is rejected' => ['draft', 'partially_paid', 422],
    'draft to paid is rejected' => ['draft', 'paid', 422],
    'sent to paid is rejected' => ['sent', 'paid', 422],
    'sent to partially_paid is rejected' => ['sent', 'partially_paid', 422],
    'partially_paid resent unchanged is allowed' => ['partially_paid', 'partially_paid', 200],
    'paid resent unchanged is allowed' => ['paid', 'paid', 200],
    'sent to overdue is allowed' => ['sent', 'overdue', 200],
]);

test('customer invoice status message is translated for en and id', function (
    string $locale,
    string $expected,
) {
    $invoice = CustomerInvoice::factory()->create(['status' => 'draft']);

    $response = putJson('/api/customer-invoices/' . $invoice->id, [
        'status' => 'paid',
    ], ['X-Locale' => $locale])->assertStatus(422);

    expect($response->json('errors.status.0'))->toBe($expected);
})->with([
    ['en', 'Cannot change status from draft to paid.'],
    ['id', 'Tidak dapat mengubah status dari draft ke paid.'],
]);
