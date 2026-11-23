<?php

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('goods-receipts');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'goods_receipt',
        'goods_receipt.create',
        'purchase_order',
    ]);
    Sanctum::actingAs($user, ['*']);
});

function purchaseOrderWithLine(): array
{
    $purchaseOrder = PurchaseOrder::factory()->create();
    $line = $purchaseOrder->items()->create([
        'product_id' => Product::factory()->create()->id,
        'unit_id' => Unit::factory()->create()->id,
        'quantity' => 10,
        'unit_price' => 5000,
        'discount_percent' => 0,
        'tax_percent' => 0,
        'line_total' => 50000,
    ]);

    return [$purchaseOrder, $line];
}

function goodsReceiptPayloadForLine(int $purchaseOrderId, int $purchaseOrderItemId): array
{
    return [
        'purchase_order_id' => $purchaseOrderId,
        'warehouse_id' => Warehouse::factory()->create()->id,
        'receipt_date' => '2026-03-06',
        'status' => 'draft',
        'items' => [
            [
                'purchase_order_item_id' => $purchaseOrderItemId,
                'product_id' => Product::factory()->create()->id,
                'unit_id' => Unit::factory()->create()->id,
                'quantity_received' => 1,
                'quantity_accepted' => 1,
                'quantity_rejected' => 0,
                'unit_price' => 5000,
            ],
        ],
    ];
}

test('store accepts a PO line that belongs to the selected purchase order', function () {
    [$purchaseOrder, $line] = purchaseOrderWithLine();

    postJson('/api/goods-receipts', goodsReceiptPayloadForLine($purchaseOrder->id, $line->id))
        ->assertCreated();
});

test('store rejects a PO line that belongs to a different purchase order', function () {
    [$purchaseOrder] = purchaseOrderWithLine();
    [, $otherLine] = purchaseOrderWithLine();

    postJson('/api/goods-receipts', goodsReceiptPayloadForLine($purchaseOrder->id, $otherLine->id))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['items.0.purchase_order_item_id']);
});

test('item-options lists only the lines of the purchase order with remaining quantity', function () {
    [$purchaseOrder, $line] = purchaseOrderWithLine();
    $line->forceFill(['quantity_received' => 4])->saveQuietly();
    purchaseOrderWithLine();

    $response = getJson("/api/purchase-orders/{$purchaseOrder->id}/item-options");

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.id'))->toBe($line->id)
        ->and($response->json('data.0.remaining_quantity'))->toBe('6')
        ->and($response->json('data.0.unit_price'))->toBe('5000.00');
});

test('goods receipt item-options returns accepted quantity minus active returns', function () {
    $goodsReceipt = GoodsReceipt::factory()->create();
    $item = GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $goodsReceipt->id,
        'quantity_accepted' => 10,
    ]);

    $response = getJson("/api/goods-receipts/{$goodsReceipt->id}/item-options");

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.id'))->toBe($item->id)
        ->and($response->json('data.0.remaining_quantity'))->toBe('10');
});
