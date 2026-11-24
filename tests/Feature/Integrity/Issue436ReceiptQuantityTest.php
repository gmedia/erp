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

uses(RefreshDatabase::class)->group('issue-436-receipt-quantity');

/**
 * Issue #436 butir 2 dan keputusan product owner (1):
 * - quantity_accepted + quantity_rejected tidak boleh melebihi quantity_received.
 * - Akumulasi qty diterima per baris PO (GR confirmed) tidak boleh melebihi qty PO. Tanpa toleransi.
 */
beforeEach(function () {
    $user = createTestUserWithPermissions([
        'goods_receipt',
        'goods_receipt.create',
        'goods_receipt.edit',
    ]);
    Sanctum::actingAs($user, ['*']);

    FiscalYear::factory()->create([
        'name' => '2026',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'open',
    ]);

    $coaVersion = CoaVersion::factory()->create(['status' => 'active']);

    foreach ([
        ['11300', 'Persediaan', 'asset', 'debit'],
        ['21150', 'Barang Diterima Belum Ditagih', 'liability', 'credit'],
    ] as [$code, $name, $type, $normal]) {
        Account::factory()->create([
            'coa_version_id' => $coaVersion->id,
            'code' => $code,
            'name' => $name,
            'type' => $type,
            'normal_balance' => $normal,
            'is_active' => true,
        ]);
    }
});

/**
 * @return array{0: PurchaseOrder, 1: PurchaseOrderItem}
 */
function issue436ConfirmedPo(int $qty): array
{
    $purchaseOrder = PurchaseOrder::factory()->create(['status' => 'confirmed']);
    $item = $purchaseOrder->items()->create([
        'product_id' => Product::factory()->create()->id,
        'unit_id' => Unit::factory()->create()->id,
        'quantity' => $qty,
        'unit_price' => 5000,
        'discount_percent' => 0,
        'tax_percent' => 0,
        'line_total' => 5000 * $qty,
    ]);

    return [$purchaseOrder, $item];
}

/**
 * @return array<string, mixed>
 */
function issue436ReceiptPayload(
    PurchaseOrder $purchaseOrder,
    PurchaseOrderItem $item,
    string $status,
    float $received,
    float $accepted,
    float $rejected = 0,
): array {
    return [
        'purchase_order_id' => $purchaseOrder->id,
        'warehouse_id' => $purchaseOrder->warehouse_id,
        'receipt_date' => now()->toDateString(),
        'status' => $status,
        'items' => [[
            'purchase_order_item_id' => $item->id,
            'product_id' => $item->product_id,
            'unit_id' => $item->unit_id,
            'quantity_received' => $received,
            'quantity_accepted' => $accepted,
            'quantity_rejected' => $rejected,
            'unit_price' => 5000,
        ]],
    ];
}

test('accepted quantity above received quantity is rejected with 422', function () {
    [$purchaseOrder, $item] = issue436ConfirmedPo(10);

    postJson('/api/goods-receipts', issue436ReceiptPayload($purchaseOrder, $item, 'draft', 4, 9))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['items.0.quantity_accepted']);

    expect(GoodsReceipt::query()->where('purchase_order_id', $purchaseOrder->id)->count())->toBe(0);
});

test('accepted plus rejected above received quantity is rejected with 422', function () {
    [$purchaseOrder, $item] = issue436ConfirmedPo(10);

    postJson('/api/goods-receipts', issue436ReceiptPayload($purchaseOrder, $item, 'draft', 4, 3, 2))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['items.0.quantity_accepted']);
});

test('accepted plus rejected equal to received quantity is accepted', function () {
    [$purchaseOrder, $item] = issue436ConfirmedPo(10);

    postJson('/api/goods-receipts', issue436ReceiptPayload($purchaseOrder, $item, 'draft', 4, 3, 1))
        ->assertStatus(201);
});

test('confirmed GR exceeding PO line quantity across receipts is rejected with 422', function () {
    [$purchaseOrder, $item] = issue436ConfirmedPo(10);

    postJson('/api/goods-receipts', issue436ReceiptPayload($purchaseOrder, $item, 'confirmed', 8, 8))
        ->assertStatus(201);

    postJson('/api/goods-receipts', issue436ReceiptPayload($purchaseOrder, $item, 'confirmed', 8, 8))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['items.0.quantity_received']);

    expect(GoodsReceipt::query()->where('purchase_order_id', $purchaseOrder->id)->count())->toBe(1);
});

test('draft GR exceeding PO line quantity is not blocked', function () {
    [$purchaseOrder, $item] = issue436ConfirmedPo(10);

    postJson('/api/goods-receipts', issue436ReceiptPayload($purchaseOrder, $item, 'draft', 8, 8))
        ->assertStatus(201);
    postJson('/api/goods-receipts', issue436ReceiptPayload($purchaseOrder, $item, 'draft', 8, 8))
        ->assertStatus(201);
});

test('accepted over received returns localized message in indonesian', function () {
    [$purchaseOrder, $item] = issue436ConfirmedPo(10);
    app()->setLocale('id');

    $response = postJson('/api/goods-receipts', issue436ReceiptPayload($purchaseOrder, $item, 'draft', 4, 9));

    $response->assertStatus(422);
    expect($response->json('errors')['items.0.quantity_accepted'][0] ?? null)
        ->toBe(__('goods_receipt.accepted_exceeds_received', [], 'id'));
});

test('editing confirmed GR item beyond PO line quantity is rejected with 422', function () {
    [$purchaseOrder, $item] = issue436ConfirmedPo(10);

    // GR A confirmed 4 dari 10; GR B confirmed 6 (total 10, masih pas).
    $first = postJson('/api/goods-receipts', issue436ReceiptPayload($purchaseOrder, $item, 'confirmed', 4, 4));
    $first->assertStatus(201);
    postJson('/api/goods-receipts', issue436ReceiptPayload($purchaseOrder, $item, 'confirmed', 6, 6))
        ->assertStatus(201);

    // Edit GR A menjadi 5 -> total 11 > 10 harus ditolak.
    $payload = issue436ReceiptPayload($purchaseOrder, $item, 'confirmed', 5, 5);

    putJson('/api/goods-receipts/' . $first->json('data.id'), $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['items.0.quantity_received']);

    $stored = GoodsReceipt::query()->findOrFail($first->json('data.id'));

    expect($stored->items()->value('quantity_received'))->toEqual(4.0);
});

test('confirming draft GR with status only is checked against stored items', function () {
    [$purchaseOrder, $item] = issue436ConfirmedPo(10);

    $draft = postJson('/api/goods-receipts', issue436ReceiptPayload($purchaseOrder, $item, 'draft', 8, 8));
    $draft->assertStatus(201);
    postJson('/api/goods-receipts', issue436ReceiptPayload($purchaseOrder, $item, 'confirmed', 8, 8))
        ->assertStatus(201);

    putJson('/api/goods-receipts/' . $draft->json('data.id'), ['status' => 'confirmed'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['items.0.quantity_received']);

    expect(GoodsReceipt::query()->findOrFail($draft->json('data.id'))->status)->toBe('draft');
});

test('two lines of one GR for the same PO line are summed against PO quantity', function () {
    [$purchaseOrder, $item] = issue436ConfirmedPo(10);

    $payload = issue436ReceiptPayload($purchaseOrder, $item, 'confirmed', 6, 6);
    $payload['items'][] = $payload['items'][0];

    postJson('/api/goods-receipts', $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['items.0.quantity_received']);

    expect(GoodsReceipt::query()->where('purchase_order_id', $purchaseOrder->id)->count())->toBe(0);
});

test('rejected quantity does not count against PO line quantity', function () {
    [$purchaseOrder, $item] = issue436ConfirmedPo(10);

    // Diterima 10, ditolak 2, diterima baik 8: sah.
    postJson('/api/goods-receipts', issue436ReceiptPayload($purchaseOrder, $item, 'confirmed', 10, 8, 2))
        ->assertStatus(201);

    // Dikirim ulang 2 yang ditolak sebelumnya: accepted total 10, masih sah.
    postJson('/api/goods-receipts', issue436ReceiptPayload($purchaseOrder, $item, 'confirmed', 2, 2))
        ->assertStatus(201);
});

test('confirmed GR within PO line quantity is accepted', function () {
    [$purchaseOrder, $item] = issue436ConfirmedPo(10);

    postJson('/api/goods-receipts', issue436ReceiptPayload($purchaseOrder, $item, 'confirmed', 10, 10))
        ->assertStatus(201);
});
