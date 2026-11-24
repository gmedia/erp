<?php

use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SupplierReturn;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('issue-436-supplier-return-quantity');

/**
 * Issue #436 butir 4 dan keputusan product owner (2): total retur per baris GR (retur tidak
 * cancelled) tidak boleh melebihi quantity_accepted baris GR confirmed. Retur setelah bill lunas tetap boleh.
 */
beforeEach(function () {
    $user = createTestUserWithPermissions([
        'goods_receipt',
        'goods_receipt.create',
        'goods_receipt.edit',
        'supplier_return',
        'supplier_return.create',
        'supplier_return.edit',
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
 * Membuat GR confirmed dengan received/accepted tertentu. Dikembalikan item GR-nya.
 */
function issue436ConfirmedGrLineForReturn(float $received, float $accepted): GoodsReceiptItem
{
    $purchaseOrder = PurchaseOrder::factory()->create(['status' => 'confirmed']);
    $poItem = PurchaseOrderItem::query()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'product_id' => Product::factory()->create()->id,
        'unit_id' => Unit::factory()->create()->id,
        'quantity' => 100,
        'unit_price' => 5000,
        'discount_percent' => 0,
        'tax_percent' => 0,
        'line_total' => 500000,
    ]);

    $response = postJson('/api/goods-receipts', [
        'purchase_order_id' => $purchaseOrder->id,
        'warehouse_id' => $purchaseOrder->warehouse_id,
        'receipt_date' => now()->toDateString(),
        'status' => 'confirmed',
        'items' => [[
            'purchase_order_item_id' => $poItem->id,
            'product_id' => $poItem->product_id,
            'unit_id' => $poItem->unit_id,
            'quantity_received' => $received,
            'quantity_accepted' => $accepted,
            'quantity_rejected' => $received - $accepted,
            'unit_price' => 5000,
        ]],
    ])->assertStatus(201);

    return GoodsReceiptItem::query()
        ->where('goods_receipt_id', $response->json('data.id'))
        ->firstOrFail();
}

/**
 * @return array<string, mixed>
 */
function issue436SupplierReturnPayload(GoodsReceiptItem $grItem, float $qty, string $status = 'draft'): array
{
    $goodsReceipt = GoodsReceipt::query()->findOrFail($grItem->goods_receipt_id);

    return [
        'goods_receipt_id' => $goodsReceipt->id,
        'purchase_order_id' => $goodsReceipt->purchase_order_id,
        'warehouse_id' => $goodsReceipt->warehouse_id,
        'supplier_id' => $goodsReceipt->purchaseOrder->supplier_id,
        'return_date' => now()->toDateString(),
        'reason' => 'defective',
        'status' => $status,
        'items' => [[
            'goods_receipt_item_id' => $grItem->id,
            'product_id' => $grItem->product_id,
            'unit_id' => $grItem->unit_id,
            'quantity_returned' => $qty,
            'unit_price' => 5000,
        ]],
    ];
}

test('return above accepted quantity is rejected with 422', function () {
    // Diterima 10, diterima baik 8 (2 ditolak): retur 9 harus ditolak.
    $grItem = issue436ConfirmedGrLineForReturn(10, 8);

    postJson('/api/supplier-returns', issue436SupplierReturnPayload($grItem, 9, 'confirmed'))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['items.0.quantity_returned']);

    expect(SupplierReturn::query()->count())->toBe(0);
});

test('return cumulative above accepted quantity across returns is rejected with 422', function () {
    $grItem = issue436ConfirmedGrLineForReturn(10, 10);

    postJson('/api/supplier-returns', issue436SupplierReturnPayload($grItem, 6, 'confirmed'))
        ->assertStatus(201);

    postJson('/api/supplier-returns', issue436SupplierReturnPayload($grItem, 5, 'confirmed'))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['items.0.quantity_returned']);
});

test('draft return does not consume quantity until confirmed', function () {
    $grItem = issue436ConfirmedGrLineForReturn(10, 10);

    // Draft 8 tidak mengikat kuota: dua draft 8 sama-sama boleh dibuat.
    postJson('/api/supplier-returns', issue436SupplierReturnPayload($grItem, 8, 'draft'))
        ->assertStatus(201);
    postJson('/api/supplier-returns', issue436SupplierReturnPayload($grItem, 8, 'draft'))
        ->assertStatus(201);

    // Konfirmasi pertama 8 sah; konfirmasi kedua 8 melewati 10 dan ditolak.
    postJson('/api/supplier-returns', issue436SupplierReturnPayload($grItem, 8, 'confirmed'))
        ->assertStatus(201);
    postJson('/api/supplier-returns', issue436SupplierReturnPayload($grItem, 8, 'confirmed'))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['items.0.quantity_returned']);
});

test('confirming a draft return with status only is checked against stored items', function () {
    $grItem = issue436ConfirmedGrLineForReturn(10, 10);

    $first = postJson('/api/supplier-returns', issue436SupplierReturnPayload($grItem, 8, 'confirmed'));
    $first->assertStatus(201);

    $draft = postJson('/api/supplier-returns', issue436SupplierReturnPayload($grItem, 5, 'draft'));
    $draft->assertStatus(201);

    putJson('/api/supplier-returns/' . $draft->json('data.id'), ['status' => 'confirmed'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['items.0.quantity_returned']);

    expect(SupplierReturn::query()->findOrFail($draft->json('data.id'))->status)->toBe('draft');
});

test('return of a line from a draft goods receipt is rejected', function () {
    $purchaseOrder = PurchaseOrder::factory()->create(['status' => 'confirmed']);
    $poItem = PurchaseOrderItem::query()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'product_id' => Product::factory()->create()->id,
        'unit_id' => Unit::factory()->create()->id,
        'quantity' => 100,
        'unit_price' => 5000,
        'discount_percent' => 0,
        'tax_percent' => 0,
        'line_total' => 500000,
    ]);

    $gr = postJson('/api/goods-receipts', [
        'purchase_order_id' => $purchaseOrder->id,
        'warehouse_id' => $purchaseOrder->warehouse_id,
        'receipt_date' => now()->toDateString(),
        'status' => 'draft',
        'items' => [[
            'purchase_order_item_id' => $poItem->id,
            'product_id' => $poItem->product_id,
            'unit_id' => $poItem->unit_id,
            'quantity_received' => 5,
            'quantity_accepted' => 5,
            'unit_price' => 5000,
        ]],
    ])->assertStatus(201);

    $grItem = GoodsReceiptItem::query()->where('goods_receipt_id', $gr->json('data.id'))->firstOrFail();

    postJson('/api/supplier-returns', issue436SupplierReturnPayload($grItem, 1, 'confirmed'))
        ->assertStatus(422);
});
