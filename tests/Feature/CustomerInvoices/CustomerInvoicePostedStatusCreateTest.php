<?php

use App\Models\Account;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\FiscalYear;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('customer-invoices');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'customer_invoice',
        'customer_invoice.create',
    ]);
    Sanctum::actingAs($user, ['*']);
});

test('creating a customer invoice with a payment-result status is rejected with 422', function (string $status) {
    $customer = Customer::factory()->create();
    $branch = Branch::factory()->create();
    $fiscalYear = FiscalYear::factory()->create();
    $product = Product::factory()->create();
    $account = Account::factory()->create();

    postJson('/api/customer-invoices', [
        'customer_id' => $customer->id,
        'branch_id' => $branch->id,
        'fiscal_year_id' => $fiscalYear->id,
        'invoice_date' => '2026-03-05',
        'due_date' => '2026-04-05',
        'payment_terms' => 'Net 30',
        'currency' => 'IDR',
        'status' => $status,
        'items' => [
            [
                'product_id' => $product->id,
                'account_id' => $account->id,
                'description' => 'Product sale',
                'quantity' => 10,
                'unit_price' => 5000,
                'discount_percent' => 0,
                'tax_percent' => 0,
            ],
        ],
    ])->assertStatus(422)->assertJsonValidationErrors(['status']);

    expect(CustomerInvoice::query()->where('customer_id', $customer->id)->exists())->toBeFalse();
})->with(['paid', 'partially_paid', 'overdue']);
