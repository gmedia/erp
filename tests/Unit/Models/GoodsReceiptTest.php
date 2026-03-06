<?php

use App\Models\Employee;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('goods-receipts');

test('goods receipt has expected relationships', function () {
    $goodsReceipt = GoodsReceipt::factory()->create([
        'received_by' => Employee::factory(),
    ]);
    $item = GoodsReceiptItem::factory()->create(['goods_receipt_id' => $goodsReceipt->id]);

    expect($goodsReceipt->purchaseOrder)->toBeInstanceOf(PurchaseOrder::class)
        ->and($goodsReceipt->warehouse)->toBeInstanceOf(Warehouse::class)
        ->and($goodsReceipt->receiver)->toBeInstanceOf(Employee::class)
        ->and($goodsReceipt->creator)->toBeInstanceOf(User::class)
        ->and($goodsReceipt->items->first()?->id)->toBe($item->id);
});
