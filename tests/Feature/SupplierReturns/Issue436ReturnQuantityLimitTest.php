<?php

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SupplierReturn;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

/**
 * Issue #436 butir 4 dan keputusan product owner (2): retur setelah bill lunas tetap boleh,
 * tetapi total retur per GR item (retur non-cancelled) tidak boleh melebihi qty diterima.
 */
uses(RefreshDatabase::class)->group('issue-436-return-quantity-limit');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'supplier_return',
        'supplier_return.create',
        'supplier_return.edit',
    ]);
    Sanctum::actingAs($user, ['*']);
});

/**
 * @return array{0: GoodsReceipt, 1: GoodsReceiptItem}
 */
function issue436ReturnableLine(float $received): array
{
    $goodsReceipt = GoodsReceipt::factory()->create();
    $line = GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $goodsReceipt->id,
        'quantity_received' => $received,
        'quantity_accepted' => $received,
        'quantity_rejected' => 0,
    ]);

    return [$goodsReceipt, $line];
}

/**
 * @return array<string, mixed>
 */
function issue436ReturnPayload(
    GoodsReceipt $goodsReceipt,
    GoodsReceiptItem $line,
    float $returned,
    string $status = 'draft',
): array {
    $purchaseOrder = PurchaseOrder::findOrFail($goodsReceipt->purchase_order_id);

    return [
        'purchase_order_id' => $purchaseOrder->id,
        'goods_receipt_id' => $goodsReceipt->id,
        'supplier_id' => $purchaseOrder->supplier_id,
        'warehouse_id' => Warehouse::factory()->create()->id,
        'return_date' => '2026-03-06',
        'reason' => 'defective',
        'status' => $status,
        'items' => [[
            'goods_receipt_item_id' => $line->id,
            'product_id' => $line->product_id,
            'unit_id' => $line->unit_id ?? Unit::factory()->create()->id,
            'quantity_returned' => $returned,
            'unit_price' => 5000,
        ]],
    ];
}

test('retur melebihi qty diterima GR item ditolak 422', function () {
    [$goodsReceipt, $line] = issue436ReturnableLine(10);

    postJson('/api/supplier-returns', issue436ReturnPayload($goodsReceipt, $line, 11))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['items.0.quantity_returned']);

    expect(SupplierReturn::query()->count())->toBe(0);
});

test('retur kedua untuk GR item yang sama ditolak bila total melampaui qty diterima', function () {
    [$goodsReceipt, $line] = issue436ReturnableLine(10);

    postJson('/api/supplier-returns', issue436ReturnPayload($goodsReceipt, $line, 6))
        ->assertCreated();

    postJson('/api/supplier-returns', issue436ReturnPayload($goodsReceipt, $line, 5))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['items.0.quantity_returned']);

    expect(SupplierReturn::query()->count())->toBe(1);
});

test('retur kedua untuk GR item yang sama diterima bila total tidak melampaui qty diterima', function () {
    [$goodsReceipt, $line] = issue436ReturnableLine(10);

    postJson('/api/supplier-returns', issue436ReturnPayload($goodsReceipt, $line, 6))
        ->assertCreated();

    postJson('/api/supplier-returns', issue436ReturnPayload($goodsReceipt, $line, 4))
        ->assertCreated();
});

test('retur cancelled tidak mengurangi sisa qty yang boleh diretur', function () {
    [$goodsReceipt, $line] = issue436ReturnableLine(10);

    $first = postJson('/api/supplier-returns', issue436ReturnPayload($goodsReceipt, $line, 8))
        ->assertCreated();

    putJson('/api/supplier-returns/' . $first->json('data.id'), ['status' => 'cancelled'])
        ->assertOk();

    postJson('/api/supplier-returns', issue436ReturnPayload($goodsReceipt, $line, 10))
        ->assertCreated();
});

test('edit retur yang menaikkan qty melampaui sisa GR item ditolak 422', function () {
    [$goodsReceipt, $line] = issue436ReturnableLine(10);

    $first = postJson('/api/supplier-returns', issue436ReturnPayload($goodsReceipt, $line, 6))
        ->assertCreated();
    postJson('/api/supplier-returns', issue436ReturnPayload($goodsReceipt, $line, 3))
        ->assertCreated();

    // Retur pertama naik 6 -> 8: total 8 + 3 = 11 > 10 harus ditolak.
    $payload = issue436ReturnPayload($goodsReceipt, $line, 8);
    unset($payload['purchase_order_id'], $payload['goods_receipt_id'], $payload['supplier_id'], $payload['warehouse_id']);

    putJson('/api/supplier-returns/' . $first->json('data.id'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['items.0.quantity_returned']);

    expect((float) SupplierReturn::query()->findOrFail($first->json('data.id'))->items()->value('quantity_returned'))
        ->toBe(6.0);
});
