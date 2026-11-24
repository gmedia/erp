<?php

use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\SupplierReturn;
use App\Models\Unit;
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

    // Retur confirmed memposting jurnal: butuh periode dan COA aktif (2026-03-06).
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
 * @return array{0: GoodsReceipt, 1: GoodsReceiptItem}
 */
function issue436ReturnableLine(float $received): array
{
    // Retur hanya boleh untuk baris GR confirmed (butir 4). Factory memilih status acak, jadi dipaksa.
    $goodsReceipt = GoodsReceipt::factory()->create(['status' => 'confirmed']);
    $line = GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $goodsReceipt->id,
        'quantity_received' => $received,
        'quantity_accepted' => $received,
        'quantity_rejected' => 0,
    ]);

    // Stok masuk sah dari penerimaan, supaya konfirmasi retur tidak membuat saldo negatif (#356).
    StockMovement::factory()->create([
        'movement_type' => 'goods_receipt',
        'reference_type' => GoodsReceipt::class,
        'reference_id' => $goodsReceipt->id,
        'product_id' => $line->product_id,
        'warehouse_id' => $goodsReceipt->warehouse_id,
        'quantity_in' => $received,
        'quantity_out' => 0,
        'balance_after' => $received,
        'moved_at' => now()->subDay(),
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
        'warehouse_id' => $goodsReceipt->warehouse_id,
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

    postJson('/api/supplier-returns', issue436ReturnPayload($goodsReceipt, $line, 11, 'confirmed'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['items.0.quantity_returned']);

    expect(SupplierReturn::query()->count())->toBe(0);
});

test('retur kedua untuk GR item yang sama ditolak bila total melampaui qty diterima', function () {
    [$goodsReceipt, $line] = issue436ReturnableLine(10);

    postJson('/api/supplier-returns', issue436ReturnPayload($goodsReceipt, $line, 6, 'confirmed'))
        ->assertCreated();

    postJson('/api/supplier-returns', issue436ReturnPayload($goodsReceipt, $line, 5, 'confirmed'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['items.0.quantity_returned']);

    expect(SupplierReturn::query()->count())->toBe(1);
});

test('retur kedua untuk GR item yang sama diterima bila total tidak melampaui qty diterima', function () {
    [$goodsReceipt, $line] = issue436ReturnableLine(10);

    postJson('/api/supplier-returns', issue436ReturnPayload($goodsReceipt, $line, 6, 'confirmed'))
        ->assertCreated();

    postJson('/api/supplier-returns', issue436ReturnPayload($goodsReceipt, $line, 4, 'confirmed'))
        ->assertCreated();
});

test('draft retur tidak mengikat kuota GR item sampai dikonfirmasi', function () {
    [$goodsReceipt, $line] = issue436ReturnableLine(10);

    postJson('/api/supplier-returns', issue436ReturnPayload($goodsReceipt, $line, 8))
        ->assertCreated();

    postJson('/api/supplier-returns', issue436ReturnPayload($goodsReceipt, $line, 10, 'confirmed'))
        ->assertCreated();
});

test('retur cancelled tidak mengurangi sisa qty yang boleh diretur', function () {
    [$goodsReceipt, $line] = issue436ReturnableLine(10);

    $first = postJson('/api/supplier-returns', issue436ReturnPayload($goodsReceipt, $line, 8, 'confirmed'))
        ->assertCreated();

    putJson('/api/supplier-returns/' . $first->json('data.id'), ['status' => 'cancelled'])
        ->assertOk();

    postJson('/api/supplier-returns', issue436ReturnPayload($goodsReceipt, $line, 10, 'confirmed'))
        ->assertCreated();
});

test('konfirmasi retur draft yang melampaui sisa GR item ditolak 422', function () {
    [$goodsReceipt, $line] = issue436ReturnableLine(10);

    // Draft boleh diedit dan tidak mengikat kuota (butir 4). Retur confirmed 3 sudah mengikat.
    $draft = postJson('/api/supplier-returns', issue436ReturnPayload($goodsReceipt, $line, 6))
        ->assertCreated();
    postJson('/api/supplier-returns', issue436ReturnPayload($goodsReceipt, $line, 3, 'confirmed'))
        ->assertCreated();

    // Draft naik 6 -> 8 lalu dikonfirmasi: 8 + 3 = 11 > 10 harus ditolak saat konfirmasi.
    $payload = issue436ReturnPayload($goodsReceipt, $line, 8, 'confirmed');
    unset($payload['purchase_order_id'], $payload['goods_receipt_id']);
    unset($payload['supplier_id'], $payload['warehouse_id']);

    putJson('/api/supplier-returns/' . $draft->json('data.id'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['items.0.quantity_returned']);

    expect(SupplierReturn::query()->findOrFail($draft->json('data.id'))->status)->toBe('draft');
    expect((float) SupplierReturn::query()->findOrFail($draft->json('data.id'))->items()->value('quantity_returned'))
        ->toBe(6.0);
});
