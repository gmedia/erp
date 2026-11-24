<?php

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('supplier-returns');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'supplier_return',
        'supplier_return.create',
    ]);
    Sanctum::actingAs($user, ['*']);
});

function goodsReceiptWithLine(): array
{
    $goodsReceipt = GoodsReceipt::factory()->create();
    $line = GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $goodsReceipt->id,
        'quantity_accepted' => 10,
    ]);

    return [$goodsReceipt, $line];
}

function supplierReturnPayloadForLine(GoodsReceipt $goodsReceipt, int $goodsReceiptItemId): array
{
    $purchaseOrder = PurchaseOrder::findOrFail($goodsReceipt->purchase_order_id);

    return [
        'purchase_order_id' => $purchaseOrder->id,
        'goods_receipt_id' => $goodsReceipt->id,
        'supplier_id' => $purchaseOrder->supplier_id,
        'warehouse_id' => Warehouse::factory()->create()->id,
        'return_date' => '2026-03-06',
        'reason' => 'defective',
        'status' => 'draft',
        'items' => [
            [
                'goods_receipt_item_id' => $goodsReceiptItemId,
                'product_id' => Product::factory()->create()->id,
                'unit_id' => Unit::factory()->create()->id,
                'quantity_returned' => 1,
                'unit_price' => 5000,
            ],
        ],
    ];
}

test('store accepts a GR line that belongs to the selected goods receipt', function () {
    [$goodsReceipt, $line] = goodsReceiptWithLine();

    postJson('/api/supplier-returns', supplierReturnPayloadForLine($goodsReceipt, $line->id))
        ->assertCreated();
});

test('store rejects a GR line that belongs to a different goods receipt', function () {
    [$goodsReceipt] = goodsReceiptWithLine();
    [, $otherLine] = goodsReceiptWithLine();

    postJson('/api/supplier-returns', supplierReturnPayloadForLine($goodsReceipt, $otherLine->id))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['items.0.goods_receipt_item_id']);
});
