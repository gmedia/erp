<?php

use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('goods-receipts');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'goods_receipt',
        'goods_receipt.create',
        'goods_receipt.edit',
    ]);
    Sanctum::actingAs($user, ['*']);
});

/**
 * Fixture COA aktif untuk jurnal GR confirmed (persediaan 11300, GR/IR 21150).
 * Dibutuhkan hanya oleh test yang memang menguji jalur confirmed diterima.
 */
function grGatePostingFixture(): void
{
    FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);

    $coaVersion = CoaVersion::factory()->create(['status' => 'active']);

    Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '11300',
        'name' => 'Persediaan',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);

    Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '21150',
        'name' => 'Barang Diterima Belum Ditagih',
        'type' => 'liability',
        'normal_balance' => 'credit',
        'is_active' => true,
    ]);
}

/**
 * @return array{0: PurchaseOrder, 1: PurchaseOrderItem}
 */
function purchaseOrderWithStatusForGoodsReceiptGate(string $status): array
{
    $purchaseOrder = PurchaseOrder::factory()->create(['status' => $status]);
    $item = $purchaseOrder->items()->create([
        'product_id' => Product::factory()->create()->id,
        'unit_id' => Unit::factory()->create()->id,
        'quantity' => 10,
        'unit_price' => 5000,
        'discount_percent' => 0,
        'tax_percent' => 11,
        'line_total' => 55500,
    ]);

    return [$purchaseOrder, $item];
}

/**
 * @return array<string, mixed>
 */
function goodsReceiptGatePayload(PurchaseOrder $purchaseOrder, PurchaseOrderItem $item, string $status): array
{
    return [
        'purchase_order_id' => $purchaseOrder->id,
        'warehouse_id' => $purchaseOrder->warehouse_id,
        'receipt_date' => now()->toDateString(),
        'status' => $status,
        'items' => [[
            'purchase_order_item_id' => $item->id,
            'product_id' => $item->product_id,
            'unit_id' => $item->unit_id,
            'quantity_received' => 5,
            'quantity_accepted' => 5,
            'quantity_rejected' => 0,
            'unit_price' => 5000,
        ]],
    ];
}

dataset('blocked_po_statuses', [
    'draft' => ['draft'],
    'pending_approval' => ['pending_approval'],
    'rejected' => ['rejected'],
    'cancelled' => ['cancelled'],
    'closed' => ['closed'],
    'fully_received' => ['fully_received'],
]);

dataset('allowed_po_statuses', [
    'confirmed' => ['confirmed'],
    'partially_received' => ['partially_received'],
]);

test('create confirmed GR is rejected with 422 for PO status', function (string $poStatus) {
    [$purchaseOrder, $item] = purchaseOrderWithStatusForGoodsReceiptGate($poStatus);

    $response = postJson('/api/goods-receipts', goodsReceiptGatePayload($purchaseOrder, $item, 'confirmed'));

    $response->assertStatus(422)->assertJsonValidationErrors(['status']);
    expect(GoodsReceipt::query()->where('purchase_order_id', $purchaseOrder->id)->count())->toBe(0);
})->with('blocked_po_statuses');

test('update to confirmed GR is rejected with 422 for PO status', function (string $poStatus) {
    [$purchaseOrder, $item] = purchaseOrderWithStatusForGoodsReceiptGate($poStatus);
    $goodsReceipt = GoodsReceipt::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'warehouse_id' => $purchaseOrder->warehouse_id,
        'status' => 'draft',
        'confirmed_at' => null,
        'confirmed_by' => null,
    ]);

    $response = putJson(
        '/api/goods-receipts/' . $goodsReceipt->id,
        goodsReceiptGatePayload($purchaseOrder, $item, 'confirmed'),
    );

    $response->assertStatus(422)->assertJsonValidationErrors(['status']);
    expect($goodsReceipt->refresh()->status)->toBe('draft');
})->with('blocked_po_statuses');

test('create confirmed GR is accepted for PO status', function (string $poStatus) {
    grGatePostingFixture();
    [$purchaseOrder, $item] = purchaseOrderWithStatusForGoodsReceiptGate($poStatus);

    postJson('/api/goods-receipts', goodsReceiptGatePayload($purchaseOrder, $item, 'confirmed'))
        ->assertStatus(201);
})->with('allowed_po_statuses');

test('create draft GR is accepted for every PO status', function (string $poStatus) {
    [$purchaseOrder, $item] = purchaseOrderWithStatusForGoodsReceiptGate($poStatus);

    postJson('/api/goods-receipts', goodsReceiptGatePayload($purchaseOrder, $item, 'draft'))
        ->assertStatus(201);
})->with([
    ['draft'],
    ['pending_approval'],
    ['confirmed'],
    ['partially_received'],
    ['rejected'],
    ['cancelled'],
    ['closed'],
    ['fully_received'],
]);

test('blocked confirmed GR returns localized message in english', function () {
    [$purchaseOrder, $item] = purchaseOrderWithStatusForGoodsReceiptGate('draft');

    $response = postJson('/api/goods-receipts', goodsReceiptGatePayload($purchaseOrder, $item, 'confirmed'));

    $response->assertStatus(422);
    expect($response->json('errors.status.0'))->toBe(__('goods_receipt.po_not_approved', [], 'en'));
});

test('GR response exposes purchase order status for the form warning', function () {
    [$purchaseOrder, $item] = purchaseOrderWithStatusForGoodsReceiptGate('draft');

    $response = postJson('/api/goods-receipts', goodsReceiptGatePayload($purchaseOrder, $item, 'draft'));

    $response->assertStatus(201);
    expect($response->json('data.purchase_order.status'))->toBe('draft');
});

test('blocked confirmed GR returns localized message in indonesian', function () {
    [$purchaseOrder, $item] = purchaseOrderWithStatusForGoodsReceiptGate('draft');

    // SetLocaleMiddleware membaca session/cookie; di sini locale diset langsung agar
    // pesan yang diuji adalah terjemahan id, bukan fallback en.
    app()->setLocale('id');

    $response = postJson('/api/goods-receipts', goodsReceiptGatePayload($purchaseOrder, $item, 'confirmed'));

    $response->assertStatus(422);
    expect($response->json('errors.status.0'))->toBe(__('goods_receipt.po_not_approved', [], 'id'));
});
