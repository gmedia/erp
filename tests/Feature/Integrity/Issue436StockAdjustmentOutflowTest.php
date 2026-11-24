<?php

use App\Models\Account;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\StockPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

/**
 * Issue #436 butir 3: penyesuaian keluar tidak boleh membuat saldo negatif.
 * Dicek lewat assertNoNegativeStock dalam transaksi yang sama, baik saat create maupun edit approved.
 */
uses(RefreshDatabase::class)->group('issue-436-stock-adjustment-outflow');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'stock_adjustment',
        'stock_adjustment.create',
        'stock_adjustment.edit',
    ]);
    Sanctum::actingAs($user, ['*']);

    $coaVersion = createAccountingControlFixture();

    // Penyesuaian memposting jurnal ke akun selisih persediaan (51000).
    Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '51000',
        'name' => 'Selisih Persediaan',
        'type' => 'expense',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);
});

/**
 * Saldo awal +5 lewat penyesuaian masuk yang sudah approved.
 *
 * @return array{0: Warehouse, 1: Product, 2: Unit}
 */
function issue436StockWithBalance(float $quantity): array
{
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->create();
    $unit = Unit::factory()->create();

    $inbound = StockAdjustment::factory()->create([
        'warehouse_id' => $warehouse->id,
        'status' => 'approved',
    ]);
    StockAdjustmentItem::factory()->create([
        'stock_adjustment_id' => $inbound->id,
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'quantity_adjusted' => $quantity,
    ]);
    app(StockPostingService::class)->postStockAdjustment($inbound->refresh());

    return [$warehouse, $product, $unit];
}

/**
 * @return array<string, mixed>
 */
function issue436OutflowPayload(Warehouse $warehouse, Product $product, Unit $unit, float $adjusted): array
{
    return [
        'warehouse_id' => $warehouse->id,
        'adjustment_date' => now()->toDateString(),
        'adjustment_type' => 'damage',
        'status' => 'approved',
        'items' => [[
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'quantity_before' => 5,
            'quantity_adjusted' => $adjusted,
            'unit_cost' => 1000,
        ]],
    ];
}

test('create approved penyesuaian keluar melebihi stok ditolak 422 dan tidak tersimpan', function () {
    [$warehouse, $product, $unit] = issue436StockWithBalance(5);

    postJson('/api/stock-adjustments', issue436OutflowPayload($warehouse, $product, $unit, -8))
        ->assertStatus(422);

    expect(StockAdjustment::query()->where('warehouse_id', $warehouse->id)->where('status', 'approved')->count())
        ->toBe(1);
    expect((float) StockMovement::query()
        ->where('product_id', $product->id)
        ->where('warehouse_id', $warehouse->id)
        ->orderByDesc('id')
        ->value('balance_after'))
        ->toBe(5.0);
});

test('create approved penyesuaian keluar tidak melebihi stok diterima', function () {
    [$warehouse, $product, $unit] = issue436StockWithBalance(5);

    postJson('/api/stock-adjustments', issue436OutflowPayload($warehouse, $product, $unit, -5))
        ->assertStatus(201);
});

test('approve lewat PUT penyesuaian keluar melebihi stok ditolak 422 dan tidak memposting', function () {
    [$warehouse, $product, $unit] = issue436StockWithBalance(5);

    $response = postJson('/api/stock-adjustments', issue436OutflowPayload($warehouse, $product, $unit, -3))
        ->assertStatus(201);
    $adjustmentId = $response->json('data.id');

    // Dokumen dibuat pending_approval (belum terposting), lalu disetujui lewat PUT.
    StockAdjustment::query()->whereKey($adjustmentId)->update(['status' => 'pending_approval', 'approved_at' => null]);
    StockMovement::query()->where('reference_type', StockAdjustment::class)
        ->where('reference_id', $adjustmentId)->delete();

    putJson('/api/stock-adjustments/' . $adjustmentId, [
        'status' => 'approved',
        'items' => [[
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'quantity_before' => 5,
            'quantity_adjusted' => -9,
            'unit_cost' => 1000,
        ]],
    ])->assertStatus(422);

    expect(StockAdjustment::query()->whereKey($adjustmentId)->value('status'))->toBe('pending_approval');
    expect(StockMovement::query()
        ->where('reference_type', StockAdjustment::class)
        ->where('reference_id', $adjustmentId)
        ->count())->toBe(0);
});

test('edit approved penyesuaian keluar yang membuat saldo negatif ditolak 422 dan saldo tetap', function () {
    [$warehouse, $product, $unit] = issue436StockWithBalance(5);

    $response = postJson('/api/stock-adjustments', issue436OutflowPayload($warehouse, $product, $unit, -3))
        ->assertStatus(201);
    $adjustmentId = $response->json('data.id');

    putJson('/api/stock-adjustments/' . $adjustmentId, [
        'items' => [[
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'quantity_before' => 5,
            'quantity_adjusted' => -9,
            'unit_cost' => 1000,
        ]],
    ])->assertStatus(422);

    expect((float) StockMovement::query()
        ->where('product_id', $product->id)
        ->where('warehouse_id', $warehouse->id)
        ->orderByDesc('moved_at')
        ->orderByDesc('id')
        ->value('balance_after'))
        ->toBe(2.0);
});
