<?php

use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\StockPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('stock-purchasing-ar-issue-356');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'goods_receipt', 'goods_receipt.create', 'goods_receipt.edit', 'goods_receipt.delete',
        'stock_transfer', 'stock_transfer.create', 'stock_transfer.edit', 'stock_transfer.delete',
        'customer_invoice', 'customer_invoice.create', 'customer_invoice.edit', 'customer_invoice.delete',
        'stock_adjustment', 'stock_adjustment.create', 'stock_adjustment.edit', 'stock_adjustment.delete',
    ]);
    actingAs($user);

    // Issue #416: konfirmasi GR membutuhkan COA aktif dan periode fiskal.
    createAccountingControlFixture();
});

/**
 * Buat PO dengan dua baris untuk produk yang sama (bug #2) atau satu baris.
 *
 * @return array{0: PurchaseOrder, 1: Product, 2: Unit, 3: array<int, PurchaseOrderItem>}
 */
function issue356PurchaseOrder(int $lines = 1): array
{
    // Issue #380: GR confirmed hanya diterima untuk PO confirmed/partially_received.
    $purchaseOrder = PurchaseOrder::factory()->create(['status' => 'confirmed']);
    $product = Product::factory()->create();
    $unit = Unit::factory()->create();
    $items = [];

    for ($i = 0; $i < $lines; $i++) {
        $items[] = $purchaseOrder->items()->create([
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'quantity' => 10,
            'unit_price' => 5000,
            'discount_percent' => 0,
            'tax_percent' => 0,
            'line_total' => 50000,
        ]);
    }

    return [$purchaseOrder, $product, $unit, $items];
}

function issue356ConfirmGoodsReceipt(PurchaseOrder $purchaseOrder, array $lines): GoodsReceipt
{
    $goodsReceipt = GoodsReceipt::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'warehouse_id' => $purchaseOrder->warehouse_id,
        'receipt_date' => now()->toDateString(), // Issue #416: jurnal butuh tahun fiskal yang memuat tanggal ini.
        'status' => 'draft',
        'confirmed_at' => null,
        'confirmed_by' => null,
    ]);

    putJson('/api/goods-receipts/' . $goodsReceipt->id, [
        'status' => 'confirmed',
        'items' => array_map(fn (array $line) => [
            'purchase_order_item_id' => $line['purchase_order_item_id'],
            'product_id' => $line['product_id'],
            'unit_id' => $line['unit_id'],
            'quantity_received' => $line['quantity'],
            'quantity_accepted' => $line['quantity'],
            'quantity_rejected' => 0,
            'unit_price' => 5000,
        ], $lines),
    ])->assertOk();

    return $goodsReceipt->refresh();
}

describe('Issue #356 — baris 1: confirm GR memperbarui received qty PO', function () {
    test('confirm GR 4 unit menaikkan quantity_received PO item menjadi 4', function () {
        [$purchaseOrder, $product, $unit, $items] = issue356PurchaseOrder(1);

        issue356ConfirmGoodsReceipt($purchaseOrder, [[
            'purchase_order_item_id' => $items[0]->id,
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'quantity' => 4,
        ]]);

        expect((float) $items[0]->refresh()->quantity_received)->toBe(4.0);
    });
});

describe('Issue #356 — baris 2: GR dua baris PO produk sama tidak saling menimpa', function () {
    test('GR dengan produk sama di dua baris PO (4 + 5) membukukan movement 9', function () {
        [$purchaseOrder, $product, $unit, $items] = issue356PurchaseOrder(2);

        issue356ConfirmGoodsReceipt($purchaseOrder, [
            ['purchase_order_item_id' => $items[0]->id, 'product_id' => $product->id, 'unit_id' => $unit->id, 'quantity' => 4],
            ['purchase_order_item_id' => $items[1]->id, 'product_id' => $product->id, 'unit_id' => $unit->id, 'quantity' => 5],
        ]);

        $totalIn = (float) StockMovement::query()
            ->where('product_id', $product->id)
            ->where('movement_type', 'goods_receipt')
            ->sum('quantity_in');

        expect($totalIn)->toBe(9.0);
    });
});

describe('Issue #356 — baris 3: pembatalan GR confirmed membalik stok', function () {
    test('GR confirmed lalu cancelled menghasilkan movement pembalik dan received qty kembali 0', function () {
        [$purchaseOrder, $product, $unit, $items] = issue356PurchaseOrder(1);

        $goodsReceipt = issue356ConfirmGoodsReceipt($purchaseOrder, [[
            'purchase_order_item_id' => $items[0]->id,
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'quantity' => 4,
        ]]);

        putJson('/api/goods-receipts/' . $goodsReceipt->id, ['status' => 'cancelled'])->assertOk();

        $netStock = (float) StockMovement::query()
            ->where('product_id', $product->id)
            ->where('reference_type', GoodsReceipt::class)
            ->where('reference_id', $goodsReceipt->id)
            ->selectRaw('COALESCE(SUM(quantity_in) - SUM(quantity_out), 0) as net')
            ->value('net');

        expect($netStock)->toBe(0.0)
            ->and((float) $items[0]->refresh()->quantity_received)->toBe(0.0);
    });
});

describe('Issue #356 — baris 4: hapus transfer in_transit mengembalikan stok asal', function () {
    test('batal transfer in_transit mengembalikan stok gudang asal (net 0 untuk transfer)', function () {
        $from = Warehouse::factory()->create();
        $to = Warehouse::factory()->create();
        $product = Product::factory()->create();
        $unit = Unit::factory()->create();

        $transfer = StockTransfer::factory()->create([
            'from_warehouse_id' => $from->id,
            'to_warehouse_id' => $to->id,
            'status' => 'in_transit',
        ]);
        StockTransferItem::factory()->create([
            'stock_transfer_id' => $transfer->id,
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'quantity' => 5,
            'quantity_received' => 0,
        ]);
        // Dispatch awal: stok 10 di gudang asal menjadi 5 setelah transfer_out.
        StockMovement::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $from->id,
            'movement_type' => 'goods_receipt',
            'quantity_in' => 10,
            'quantity_out' => 0,
            'balance_after' => 10,
            'reference_type' => GoodsReceipt::class,
            'reference_id' => 999001,
            'moved_at' => now()->subDay(),
        ]);
        app(StockPostingService::class)->postStockTransferDispatch($transfer->refresh());

        // #375: dokumen terposting tidak boleh di-DELETE; pembatalan lewat transisi status.
        putJson('/api/stock-transfers/' . $transfer->id, ['status' => 'cancelled'])->assertOk();

        $balance = (float) StockMovement::query()
            ->where('product_id', $product->id)
            ->where('warehouse_id', $from->id)
            ->orderByDesc('moved_at')->orderByDesc('id')
            ->value('balance_after');

        expect($balance)->toBe(10.0);
    });
});

describe('Issue #356 — baris 5: transfer received tanpa quantity_received = diterima penuh', function () {
    test('transfer received tanpa quantity_received memasukkan 5 unit ke gudang tujuan', function () {
        $from = Warehouse::factory()->create();
        $to = Warehouse::factory()->create();
        $product = Product::factory()->create();
        $unit = Unit::factory()->create();

        StockMovement::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $from->id,
            'movement_type' => 'goods_receipt',
            'quantity_in' => 10,
            'quantity_out' => 0,
            'balance_after' => 10,
            'reference_type' => GoodsReceipt::class,
            'reference_id' => 999002,
            'moved_at' => now()->subDay(),
        ]);

        $transfer = StockTransfer::factory()->create([
            'from_warehouse_id' => $from->id,
            'to_warehouse_id' => $to->id,
            'status' => 'in_transit',
        ]);
        StockTransferItem::factory()->create([
            'stock_transfer_id' => $transfer->id,
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'quantity' => 5,
            'quantity_received' => 0,
        ]);
        app(StockPostingService::class)->postStockTransferDispatch($transfer->refresh());

        putJson('/api/stock-transfers/' . $transfer->id, [
            'status' => 'received',
            'items' => [[
                'product_id' => $product->id,
                'unit_id' => $unit->id,
                'quantity' => 5,
            ]],
        ])->assertOk();

        $incoming = (float) StockMovement::query()
            ->where('product_id', $product->id)
            ->where('warehouse_id', $to->id)
            ->where('movement_type', 'transfer_in')
            ->sum('quantity_in');

        expect($incoming)->toBe(5.0);
    });
});

describe('Issue #356 — baris 6: stok negatif ditolak 422', function () {
    test('dispatch 5 unit saat stok 0 ditolak dengan 422', function () {
        $from = Warehouse::factory()->create();
        $to = Warehouse::factory()->create();
        $product = Product::factory()->create();
        $unit = Unit::factory()->create();

        $transfer = StockTransfer::factory()->create([
            'from_warehouse_id' => $from->id,
            'to_warehouse_id' => $to->id,
            'status' => 'draft',
        ]);

        putJson('/api/stock-transfers/' . $transfer->id, [
            'status' => 'in_transit',
            'items' => [[
                'product_id' => $product->id,
                'unit_id' => $unit->id,
                'quantity' => 5,
            ]],
        ])->assertStatus(422);

        expect(StockMovement::query()->where('product_id', $product->id)->where('warehouse_id', $from->id)->count())
            ->toBe(0);
    });
});

describe('Issue #356 — edit item GR confirmed setelah posting tidak menggandakan stok', function () {
    test('edit item GR confirmed menjaga total movement = qty accepted terbaru (tidak dobel)', function () {
        [$purchaseOrder, $product, $unit, $items] = issue356PurchaseOrder(1);

        $goodsReceipt = issue356ConfirmGoodsReceipt($purchaseOrder, [[
            'purchase_order_item_id' => $items[0]->id,
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'quantity' => 4,
        ]]);

        putJson('/api/goods-receipts/' . $goodsReceipt->id, [
            'items' => [[
                'purchase_order_item_id' => $items[0]->id,
                'product_id' => $product->id,
                'unit_id' => $unit->id,
                'quantity_received' => 6,
                'quantity_accepted' => 6,
                'quantity_rejected' => 0,
                'unit_price' => 5000,
            ]],
        ])->assertOk();

        $net = (float) StockMovement::query()
            ->where('product_id', $product->id)
            ->where('reference_type', GoodsReceipt::class)
            ->where('reference_id', $goodsReceipt->id)
            ->selectRaw('COALESCE(SUM(quantity_in) - SUM(quantity_out), 0) as net')
            ->value('net');

        expect($net)->toBe(6.0);
    });
});

describe('Issue #356 — sibling: adjustment approved yang dibatalkan membalik stok', function () {
    test('DELETE adjustment approved (+5) ditolak 422 dan movement tidak dibalik (#375)', function () {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();
        $unit = Unit::factory()->create();

        $adjustment = StockAdjustment::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => 'approved',
        ]);
        StockAdjustmentItem::factory()->create([
            'stock_adjustment_id' => $adjustment->id,
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'quantity_adjusted' => 5,
        ]);
        app(StockPostingService::class)->postStockAdjustment($adjustment->refresh());

        // #375: adjustment approved tidak boleh di-DELETE (422); saldo tetap, baris tetap ada.
        deleteJson('/api/stock-adjustments/' . $adjustment->id)->assertUnprocessable();
        assertDatabaseHas('stock_adjustments', ['id' => $adjustment->id, 'status' => 'approved']);

        $net = (float) StockMovement::query()
            ->where('reference_type', StockAdjustment::class)
            ->where('reference_id', $adjustment->id)
            ->selectRaw('COALESCE(SUM(quantity_in) - SUM(quantity_out), 0) as net')
            ->value('net');

        expect($net)->toBe(5.0);
    });
});

describe('Issue #356 — baris 7: amount_due disimpan 0 di DB untuk void', function () {
    test('?amount_due_min=1000&status=void kosong dan kolom DB amount_due = 0', function () {
        $customer = Customer::factory()->create();
        $invoice = CustomerInvoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'void',
            'grand_total' => 5000,
            'amount_due' => 5000,
        ]);

        $response = getJson('/api/customer-invoices?amount_due_min=1000&status=void');
        $response->assertOk();

        expect($response->json('data'))->toHaveCount(0)
            ->and((float) DB::table('customer_invoices')->where('id', $invoice->id)->value('amount_due'))
            ->toBe(0.0);
    });
});
