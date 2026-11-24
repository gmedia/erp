<?php

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SupplierReturn;
use App\Models\SupplierReturnItem;
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

test('item-options filters PO lines by the search keyword', function () {
    [$purchaseOrder, $line] = purchaseOrderWithLine();
    $line->product->forceFill(['name' => 'Kabel Tembaga Khusus'])->saveQuietly();
    $otherLine = $purchaseOrder->items()->create([
        'product_id' => Product::factory()->create(['name' => 'Lampu LED'])->id,
        'unit_id' => Unit::factory()->create()->id,
        'quantity' => 3,
        'unit_price' => 1000,
        'discount_percent' => 0,
        'tax_percent' => 0,
        'line_total' => 3000,
    ]);

    $response = getJson("/api/purchase-orders/{$purchaseOrder->id}/item-options?search=Tembaga");

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.id'))->toBe($line->id)
        ->and($response->json('data.0.id'))->not->toBe($otherLine->id);
});

test('item-options hides a fully received PO line unless it is the line being edited', function () {
    [$purchaseOrder, $line] = purchaseOrderWithLine();
    $line->forceFill(['quantity_received' => 10])->saveQuietly();

    $hidden = getJson("/api/purchase-orders/{$purchaseOrder->id}/item-options");
    expect($hidden->json('data'))->toHaveCount(0);

    $editing = getJson("/api/purchase-orders/{$purchaseOrder->id}/item-options?current_line_id={$line->id}");
    expect($editing->json('data'))->toHaveCount(1)
        ->and($editing->json('data.0.remaining_quantity'))->toBe('0');
});

test('item-options ignores the edited PO line own quantity when computing remaining', function () {
    [$purchaseOrder, $line] = purchaseOrderWithLine();
    $line->forceFill(['quantity_received' => 4])->saveQuietly();

    $response = getJson("/api/purchase-orders/{$purchaseOrder->id}/item-options?current_line_id={$line->id}");

    expect($response->json('data.0.remaining_quantity'))->toBe('6');
});

test('item-options does not count the edited goods receipt own accepted quantity against the PO line', function () {
    [$purchaseOrder, $line] = purchaseOrderWithLine();
    $goodsReceipt = GoodsReceipt::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'status' => 'confirmed',
    ]);
    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $goodsReceipt->id,
        'purchase_order_item_id' => $line->id,
        'quantity_accepted' => 4,
    ]);
    $line->forceFill(['quantity_received' => 4])->saveQuietly();

    $response = getJson(
        "/api/purchase-orders/{$purchaseOrder->id}/item-options?current_line_id={$line->id}"
        . '&goods_receipt_id=' . $goodsReceipt->id,
    );

    expect($response->json('data.0.remaining_quantity'))->toBe('10');
});

test('goods receipt item-options hides a fully returned line unless it is the line being edited', function () {
    $goodsReceipt = GoodsReceipt::factory()->create();
    $item = GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $goodsReceipt->id,
        'quantity_accepted' => 10,
    ]);
    SupplierReturnItem::factory()->create([
        'supplier_return_id' => SupplierReturn::factory()->create(['status' => 'confirmed'])->id,
        'goods_receipt_item_id' => $item->id,
        'quantity_returned' => 10,
    ]);

    $hidden = getJson("/api/goods-receipts/{$goodsReceipt->id}/item-options");
    expect($hidden->json('data'))->toHaveCount(0);

    $editing = getJson("/api/goods-receipts/{$goodsReceipt->id}/item-options?current_line_id={$item->id}");
    expect($editing->json('data'))->toHaveCount(1)
        ->and($editing->json('data.0.remaining_quantity'))->toBe('0');
});

test('item-options on a confirmed goods receipt shows the remaining quantity without its own accepted qty', function () {
    [$purchaseOrder, $line] = purchaseOrderWithLine();
    $goodsReceipt = GoodsReceipt::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'status' => 'confirmed',
    ]);
    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $goodsReceipt->id,
        'purchase_order_item_id' => $line->id,
        'quantity_accepted' => 7,
    ]);
    $line->forceFill(['quantity_received' => 7])->saveQuietly();

    $response = getJson(
        "/api/purchase-orders/{$purchaseOrder->id}/item-options?current_line_id={$line->id}"
        . '&goods_receipt_id=' . $goodsReceipt->id,
    );

    expect($response->json('data.0.remaining_quantity'))->toBe('10');
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
