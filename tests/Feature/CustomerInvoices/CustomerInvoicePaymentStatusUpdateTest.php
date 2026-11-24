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

test('put may resend the stored payment-result status unchanged', function (string $status) {
    $invoice = CustomerInvoice::factory()->create(['status' => $status]);

    putJson('/api/customer-invoices/' . $invoice->id, [
        'status' => $status,
    ])->assertOk()->assertJsonPath('data.status', $status);

    expect($invoice->refresh()->status)->toBe($status);
})->with(['partially_paid', 'paid', 'overdue']);

test('put may mark a sent customer invoice overdue', function () {
    $invoice = CustomerInvoice::factory()->create(['status' => 'sent']);

    putJson('/api/customer-invoices/' . $invoice->id, [
        'status' => 'overdue',
    ])->assertOk()->assertJsonPath('data.status', 'overdue');
});

test('put may not move a draft customer invoice straight to overdue', function () {
    $invoice = CustomerInvoice::factory()->create(['status' => 'draft']);

    putJson('/api/customer-invoices/' . $invoice->id, [
        'status' => 'overdue',
    ])->assertStatus(422)->assertJsonValidationErrors(['status']);

    expect($invoice->refresh()->status)->toBe('draft');
});
