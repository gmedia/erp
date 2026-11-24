<?php

use App\Models\InventoryStocktake;
use App\Models\InventoryStocktakeItem;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\putJson;

/**
 * Issue #436 keputusan product owner (3): penyelesaian stocktake membuat satu draft
 * penyesuaian stok (idempoten, hanya baris selisih). Stok baru berubah setelah draft disetujui.
 */
uses(RefreshDatabase::class)->group('issue-436-stocktake-adjustment-draft');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'inventory_stocktake',
        'inventory_stocktake.edit',
        'stock_adjustment',
        'stock_adjustment.create',
    ]);
    Sanctum::actingAs($user, ['*']);
    createAccountingControlFixture();
});

/**
 * Stocktake in_progress dengan tiga baris: surplus, deficit, dan match.
 */
function issue436OpenStocktake(): InventoryStocktake
{
    $warehouse = Warehouse::factory()->create();
    $unit = Unit::factory()->create();

    $stocktake = InventoryStocktake::factory()->create([
        'warehouse_id' => $warehouse->id,
        'status' => 'in_progress',
        'completed_at' => null,
        'completed_by' => null,
    ]);

    $rows = [
        [10.0, 13.0, 'surplus'],
        [8.0, 5.0, 'deficit'],
        [4.0, 4.0, 'match'],
    ];

    foreach ($rows as [$system, $counted, $result]) {
        InventoryStocktakeItem::factory()->create([
            'inventory_stocktake_id' => $stocktake->id,
            'product_id' => Product::factory()->create()->id,
            'unit_id' => $unit->id,
            'system_quantity' => $system,
            'counted_quantity' => $counted,
            'variance' => $counted - $system,
            'result' => $result,
        ]);
    }

    return $stocktake;
}

function issue436CompleteStocktake(InventoryStocktake $stocktake): void
{
    putJson('/api/inventory-stocktakes/' . $stocktake->id, ['status' => 'completed'])
        ->assertOk();
}

test('penyelesaian stocktake membuat tepat satu draft penyesuaian hanya dari baris selisih', function () {
    $stocktake = issue436OpenStocktake();

    issue436CompleteStocktake($stocktake);

    $adjustments = StockAdjustment::where('inventory_stocktake_id', $stocktake->id)->get();
    expect($adjustments)->toHaveCount(1);

    $adjustment = $adjustments->first();
    expect($adjustment->status)->toBe('draft')
        ->and($adjustment->adjustment_type)->toBe('stocktake_result')
        ->and($adjustment->warehouse_id)->toBe($stocktake->warehouse_id)
        ->and($adjustment->journal_entry_id)->toBeNull()
        ->and($adjustment->approved_at)->toBeNull();

    $adjustment->load('items');
    expect($adjustment->items)->toHaveCount(2);
    expect($adjustment->items->pluck('quantity_adjusted')->map(fn ($v) => (float) $v)->sort()->values()->all())
        ->toBe([-3.0, 3.0]);
});

test('penyelesaian stocktake tidak mengubah stok sebelum draft penyesuaian disetujui', function () {
    $stocktake = issue436OpenStocktake();

    issue436CompleteStocktake($stocktake);

    expect(StockMovement::count())->toBe(0);
});

test('penyelesaian stocktake yang diulang tidak membuat draft penyesuaian kedua', function () {
    $stocktake = issue436OpenStocktake();

    issue436CompleteStocktake($stocktake);
    issue436CompleteStocktake($stocktake);

    expect(StockAdjustment::where('inventory_stocktake_id', $stocktake->id)->count())->toBe(1);
});

test('detail stocktake menampilkan nomor draft penyesuaian yang dibuatnya', function () {
    $stocktake = issue436OpenStocktake();
    issue436CompleteStocktake($stocktake);

    $draft = StockAdjustment::where('inventory_stocktake_id', $stocktake->id)->firstOrFail();
    $draft->update(['adjustment_number' => 'SA-2026-000001']);

    getJson('/api/inventory-stocktakes/' . $stocktake->id)
        ->assertOk()
        ->assertJsonPath('data.stock_adjustment_draft.id', $draft->id)
        ->assertJsonPath('data.stock_adjustment_draft.adjustment_number', 'SA-2026-000001')
        ->assertJsonPath('data.stock_adjustment_draft.status', 'draft');
});

test('stocktake tanpa selisih tidak membuat draft penyesuaian', function () {
    $stocktake = issue436OpenStocktake();
    $stocktake->items()->update(['counted_quantity' => 0, 'variance' => 0, 'result' => 'match']);
    $stocktake->items()->each(fn (InventoryStocktakeItem $item) => $item->update([
        'counted_quantity' => $item->system_quantity,
    ]));

    issue436CompleteStocktake($stocktake);

    expect(StockAdjustment::where('inventory_stocktake_id', $stocktake->id)->count())->toBe(0);
});
