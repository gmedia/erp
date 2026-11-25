<?php

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class)->group('stock-movements');

/**
 * GR confirmed dengan satu item; memakai jalur posting produksi.
 */
function issue495CommandReceipt(
    Warehouse $warehouse,
    Product $product,
    float $quantity,
    float $unitPrice,
    string $date,
): void {
    $purchaseOrder = PurchaseOrder::factory()->create(['warehouse_id' => $warehouse->id]);
    $receipt = GoodsReceipt::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'warehouse_id' => $warehouse->id,
        'receipt_date' => $date,
        'status' => 'confirmed',
        'confirmed_by' => User::factory()->create()->id,
        'confirmed_at' => now(),
    ]);

    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $receipt->id,
        'product_id' => $product->id,
        'quantity_received' => $quantity,
        'quantity_accepted' => $quantity,
        'quantity_rejected' => 0,
        'unit_price' => $unitPrice,
    ]);

    app(StockPostingService::class)->postGoodsReceipt($receipt->refresh());
}

/**
 * Simulasikan data lama: kolom average_cost_after belum terisi. unit_cost pada
 * movement masuk adalah biaya sebenarnya dan tetap dibiarkan.
 */
function issue495CorruptAverageCostHistory(): void
{
    StockMovement::query()->update(['average_cost_after' => null]);
}

test('dry-run tidak menulis ke database dan melaporkan baris yang perlu diperbarui', function () {
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create(['cost' => 0]);

    issue495CommandReceipt($warehouse, $product, 10, 1000, '2026-01-10');
    issue495CommandReceipt($warehouse, $product, 10, 2000, '2026-01-11');
    issue495CorruptAverageCostHistory();

    $exit = Artisan::call('stock:recalculate-average-cost', ['--dry-run' => true]);
    $output = Artisan::output();

    expect($exit)->toBe(0)
        ->and($output)->toContain('DRY RUN')
        ->and($output)->toContain('perlu diperbarui: 2');

    expect(StockMovement::query()->whereNull('average_cost_after')->count())->toBe(2);
});

test('command menghitung rata-rata tertimbang bergerak secara berurutan dan menulisnya', function () {
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create(['cost' => 0]);

    issue495CommandReceipt($warehouse, $product, 10, 1000, '2026-01-10');
    issue495CommandReceipt($warehouse, $product, 10, 2000, '2026-01-11');
    issue495CorruptAverageCostHistory();

    expect(Artisan::call('stock:recalculate-average-cost'))->toBe(0);

    $movements = StockMovement::query()
        ->where('product_id', $product->id)
        ->where('warehouse_id', $warehouse->id)
        ->orderBy('moved_at')
        ->orderBy('id')
        ->get();

    expect((float) $movements[0]->average_cost_after)->toBe(1000.0)
        ->and((float) $movements[1]->average_cost_after)->toBe(1500.0)
        ->and((float) $movements[1]->unit_cost)->toBe(2000.0);
});

test('command idempoten: menjalankan ulang tidak mengubah baris yang sudah benar', function () {
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create(['cost' => 0]);

    issue495CommandReceipt($warehouse, $product, 10, 1000, '2026-01-10');
    issue495CommandReceipt($warehouse, $product, 10, 2000, '2026-01-11');
    issue495CorruptAverageCostHistory();

    expect(Artisan::call('stock:recalculate-average-cost'))->toBe(0);
    $afterFirst = StockMovement::query()->orderBy('id')->get(['id', 'average_cost_after', 'unit_cost'])->toArray();

    expect(Artisan::call('stock:recalculate-average-cost'))->toBe(0);
    expect(Artisan::output())->toContain('perlu diperbarui: 0');
    $afterSecond = StockMovement::query()->orderBy('id')->get(['id', 'average_cost_after', 'unit_cost'])->toArray();

    expect($afterSecond)->toBe($afterFirst);
});
