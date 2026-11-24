<?php

use App\Models\ApPayment;
use App\Models\ArReceipt;
use App\Models\CreditNote;
use App\Models\CustomerInvoice;
use App\Models\GoodsReceipt;
use App\Models\StockAdjustment;
use App\Models\StockTransfer;
use App\Models\SupplierBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;

uses(RefreshDatabase::class)->group('posted-document-lifecycle-issue-375');

/*
 * Issue #375: DELETE hanya untuk dokumen draft. Dokumen terposting (punya jurnal/stok/alokasi)
 * tidak boleh hilang; respons 422 dengan pesan i18n, baris tetap ada.
 */

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'goods_receipt', 'goods_receipt.delete',
        'customer_invoice', 'customer_invoice.delete',
        'supplier_bill', 'supplier_bill.delete',
        'ar_receipt', 'ar_receipt.delete',
        'ap_payment', 'ap_payment.delete',
        'credit_note', 'credit_note.delete',
        'stock_adjustment', 'stock_adjustment.delete',
        'stock_transfer', 'stock_transfer.delete',
    ]);
    Sanctum::actingAs($user, ['*']);
});

/**
 * @return array<string, array{0: class-string, 1: string, 2: string}>
 */
function issue375Modules(): array
{
    return [
        'goods-receipts' => [GoodsReceipt::class, 'goods-receipts', 'confirmed'],
        'customer-invoices' => [CustomerInvoice::class, 'customer-invoices', 'sent'],
        'supplier-bills' => [SupplierBill::class, 'supplier-bills', 'confirmed'],
        'ar-receipts' => [ArReceipt::class, 'ar-receipts', 'confirmed'],
        'ap-payments' => [ApPayment::class, 'ap-payments', 'confirmed'],
        'credit-notes' => [CreditNote::class, 'credit-notes', 'applied'],
        'stock-adjustments' => [StockAdjustment::class, 'stock-adjustments', 'approved'],
        'stock-transfers' => [StockTransfer::class, 'stock-transfers', 'in_transit'],
    ];
}

/**
 * @return array<string, array{0: class-string, 1: string, 2: string}>
 */
function issue375DeleteCases(): array
{
    $cases = [];

    foreach (issue375Modules() as $name => [$model, $slug, $postedStatus]) {
        $cases["{$name} draft"] = [$model, $slug, 'draft', true];
        $cases["{$name} {$postedStatus}"] = [$model, $slug, $postedStatus, false];
    }

    return $cases;
}

test('DELETE hanya menghapus dokumen draft, status lain ditolak 422', function (
    string $model,
    string $slug,
    string $status,
    bool $deletable,
) {
    $document = $model::factory()->create(['status' => $status]);

    $response = deleteJson("/api/{$slug}/{$document->id}");

    if ($deletable) {
        $response->assertNoContent();
        assertDatabaseMissing($document->getTable(), ['id' => $document->id]);

        return;
    }

    $response->assertUnprocessable()->assertJsonStructure(['message']);
    assertDatabaseHas($document->getTable(), ['id' => $document->id, 'status' => $status]);
})->with(issue375DeleteCases());
