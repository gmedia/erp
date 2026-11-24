<?php

use App\Models\Account;
use App\Models\Branch;
use App\Models\FiscalYear;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('supplier-bills');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'supplier_bill',
        'supplier_bill.create',
    ]);
    Sanctum::actingAs($user, ['*']);
});

test('creating a supplier bill with a payment-result status is rejected with 422', function (string $status) {
    $supplier = Supplier::factory()->create();
    $branch = Branch::factory()->create();
    $fiscalYear = FiscalYear::factory()->create();
    $product = Product::factory()->create();
    $account = Account::factory()->create();

    postJson('/api/supplier-bills', [
        'supplier_id' => $supplier->id,
        'branch_id' => $branch->id,
        'fiscal_year_id' => $fiscalYear->id,
        'supplier_invoice_number' => 'INV-PAID-001',
        'supplier_invoice_date' => '2026-03-05',
        'bill_date' => '2026-03-05',
        'due_date' => '2026-04-05',
        'payment_terms' => 'Net 30',
        'currency' => 'IDR',
        'status' => $status,
        'items' => [
            [
                'product_id' => $product->id,
                'account_id' => $account->id,
                'description' => 'Product purchase',
                'quantity' => 10,
                'unit_price' => 5000,
                'discount_percent' => 0,
                'tax_percent' => 0,
            ],
        ],
    ])->assertStatus(422)->assertJsonValidationErrors(['status']);

    expect(SupplierBill::query()->where('supplier_invoice_number', 'INV-PAID-001')->exists())->toBeFalse();
})->with(['paid', 'partially_paid', 'overdue']);
