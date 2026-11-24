<?php

use App\Models\ArReceipt;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('ar-receipts');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'ar_receipt',
        'ar_receipt.edit',
    ]);
    Sanctum::actingAs($user, ['*']);
});

test('partial update without customer_id still rejects an invoice from another customer', function () {
    $ownerCustomer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();
    $receipt = ArReceipt::factory()->create([
        'customer_id' => $ownerCustomer->id,
        'status' => 'draft',
    ]);
    $foreignInvoice = CustomerInvoice::factory()->create([
        'customer_id' => $otherCustomer->id,
        'status' => 'sent',
        'grand_total' => 100000,
    ]);

    putJson("/api/ar-receipts/{$receipt->id}", [
        'allocations' => [
            [
                'customer_invoice_id' => $foreignInvoice->id,
                'allocated_amount' => 10000,
            ],
        ],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['allocations.0.customer_invoice_id']);
});
