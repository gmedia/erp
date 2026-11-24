<?php

use App\Actions\AccountingPosting\PostStockAdjustmentJournalAction;
use App\Actions\AccountingPosting\ResolveControlAccountAction;
use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('stock-adjustments');

describe('Stock Adjustment API Endpoints', function () {
    beforeEach(function () {
        $user = createTestUserWithPermissions([
            'stock_adjustment',
            'stock_adjustment.create',
            'stock_adjustment.edit',
            'stock_adjustment.delete',
        ]);

        actingAs($user);

        // Jurnal penyesuaian butuh COA aktif yang mencakup tanggal dokumen (Issue #416:
        // COA tidak lengkap kini menolak dokumen, jadi fixture approve harus lengkap).
        $fiscalYear = FiscalYear::factory()->create([
            'name' => 'FY-SA-TEST',
            'start_date' => '2000-01-01',
            'end_date' => '2099-12-31',
            'status' => 'open',
        ]);
        $coaVersion = CoaVersion::factory()->create([
            'fiscal_year_id' => $fiscalYear->id,
            'status' => 'active',
        ]);
        Account::factory()->create([
            'coa_version_id' => $coaVersion->id,
            'code' => '11300',
            'name' => 'Inventory',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);
        Account::factory()->create([
            'coa_version_id' => $coaVersion->id,
            'code' => '51000',
            'name' => 'Cost of Goods Sold',
            'type' => 'expense',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);
        Account::factory()->create([
            'coa_version_id' => $coaVersion->id,
            'code' => '31000',
            'name' => 'Owner Capital',
            'type' => 'equity',
            'normal_balance' => 'credit',
            'is_active' => true,
        ]);
    });

    test('index returns paginated adjustments', function () {
        StockAdjustment::factory()->count(3)->create(['status' => 'draft']);

        $response = getJson('/api/stock-adjustments?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'adjustment_number',
                        'warehouse',
                        'adjustment_date',
                        'adjustment_type',
                        'status',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => ['total', 'per_page', 'current_page'],
            ]);
    });

    test('index excludes cancelled adjustments by default', function () {
        StockAdjustment::factory()->create(['status' => 'cancelled']);
        StockAdjustment::factory()->create(['status' => 'draft']);

        $response = getJson('/api/stock-adjustments');
        $response->assertOk();

        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.status'))->toBe('draft');
    });

    test('index can include cancelled adjustments when filtered by status', function () {
        StockAdjustment::factory()->create(['status' => 'cancelled']);

        $response = getJson('/api/stock-adjustments?status=cancelled');
        $response->assertOk();

        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.status'))->toBe('cancelled');
    });

    test('store creates adjustment with items', function () {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();
        $unit = Unit::factory()->create();

        $response = postJson('/api/stock-adjustments', [
            'adjustment_number' => 'SA-TEST-0001',
            'warehouse_id' => $warehouse->id,
            'adjustment_date' => now()->toDateString(),
            'adjustment_type' => 'correction',
            'status' => 'draft',
            'notes' => 'Test notes',
            'items' => [
                [
                    'product_id' => $product->id,
                    'unit_id' => $unit->id,
                    'quantity_before' => 10,
                    'quantity_adjusted' => -2,
                    'unit_cost' => 100,
                    'reason' => null,
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonFragment(['adjustment_number' => 'SA-TEST-0001']);

        assertDatabaseHas('stock_adjustments', [
            'adjustment_number' => 'SA-TEST-0001',
            'warehouse_id' => $warehouse->id,
            'status' => 'draft',
        ]);

        assertDatabaseHas('stock_adjustment_items', [
            'product_id' => $product->id,
            'unit_id' => $unit->id,
        ]);
    });

    test('store auto-generates adjustment_number when empty', function () {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();
        $unit = Unit::factory()->create();

        $response = postJson('/api/stock-adjustments', [
            'warehouse_id' => $warehouse->id,
            'adjustment_date' => now()->toDateString(),
            'adjustment_type' => 'correction',
            'status' => 'draft',
            'notes' => null,
            'items' => [
                [
                    'product_id' => $product->id,
                    'unit_id' => $unit->id,
                    'quantity_adjusted' => 1,
                ],
            ],
        ]);

        $response->assertCreated();

        $adjustmentNumber = $response->json('data.adjustment_number');
        expect($adjustmentNumber)->toBeString()->and($adjustmentNumber)->toStartWith('SA-');
    });

    test('update modifies adjustment and syncs items', function () {
        $warehouse = Warehouse::factory()->create();
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        $unit = Unit::factory()->create();

        $adjustment = StockAdjustment::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => 'draft',
            'adjustment_date' => '2026-06-15',
        ]);

        $response = putJson("/api/stock-adjustments/{$adjustment->id}", [
            'status' => 'approved',
            'items' => [
                [
                    'product_id' => $product1->id,
                    'unit_id' => $unit->id,
                    'quantity_adjusted' => 5,
                ],
                [
                    'product_id' => $product2->id,
                    'unit_id' => $unit->id,
                    'quantity_adjusted' => -3,
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonFragment(['status' => 'approved']);

        assertDatabaseHas('stock_adjustment_items', [
            'stock_adjustment_id' => $adjustment->id,
            'product_id' => $product1->id,
        ]);
        assertDatabaseHas('stock_adjustment_items', [
            'stock_adjustment_id' => $adjustment->id,
            'product_id' => $product2->id,
        ]);
    });

    test('approving an adjustment creates stock movements per item', function () {
        $warehouse = Warehouse::factory()->create();
        $productIn = Product::factory()->create();
        $productOut = Product::factory()->create();
        $unit = Unit::factory()->create();

        $adjustment = StockAdjustment::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => 'draft',
            'approved_at' => null,
            'adjustment_date' => '2026-06-15',
        ]);

        // Item sudah tersimpan sebelum approval (alur nyata: draft → approve).
        $adjustment->items()->create([
            'product_id' => $productIn->id,
            'unit_id' => $unit->id,
            'quantity_before' => 10,
            'quantity_adjusted' => 5,
            'quantity_after' => 15,
        ]);
        $adjustment->items()->create([
            'product_id' => $productOut->id,
            'unit_id' => $unit->id,
            'quantity_before' => 10,
            'quantity_adjusted' => -3,
            'quantity_after' => 7,
        ]);

        putJson("/api/stock-adjustments/{$adjustment->id}", [
            'status' => 'approved',
        ])->assertOk();

        assertDatabaseHas('stock_movements', [
            'movement_type' => 'adjustment_in',
            'reference_type' => StockAdjustment::class,
            'reference_id' => $adjustment->id,
            'product_id' => $productIn->id,
            'quantity_in' => 5,
        ]);
        assertDatabaseHas('stock_movements', [
            'movement_type' => 'adjustment_out',
            'reference_type' => StockAdjustment::class,
            'reference_id' => $adjustment->id,
            'product_id' => $productOut->id,
            'quantity_out' => 3,
        ]);
    });

    test('update accepts an unchanged adjustment number', function () {
        $adjustment = StockAdjustment::factory()->create(['status' => 'draft']);

        putJson("/api/stock-adjustments/{$adjustment->id}", [
            'adjustment_number' => $adjustment->adjustment_number,
        ])->assertOk();

        assertDatabaseHas('stock_adjustments', [
            'id' => $adjustment->id,
            'adjustment_number' => $adjustment->adjustment_number,
        ]);
    });

    test('show returns adjustment with all relations loaded', function () {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();
        $unit = Unit::factory()->create();

        $adjustment = StockAdjustment::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => 'draft',
        ]);

        StockAdjustmentItem::factory()->create([
            'stock_adjustment_id' => $adjustment->id,
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'quantity_before' => 5,
            'quantity_adjusted' => -1,
            'quantity_after' => 4,
        ]);

        $response = getJson("/api/stock-adjustments/{$adjustment->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'adjustment_number',
                    'warehouse' => ['id', 'name'],
                    'created_by',
                    'items' => [
                        '*' => [
                            'id',
                            'product' => ['id', 'name'],
                            'unit' => ['id', 'name'],
                            'quantity_before',
                            'quantity_adjusted',
                            'quantity_after',
                        ],
                    ],
                ],
            ])
            ->assertJsonPath('data.id', $adjustment->id)
            ->assertJsonPath('data.warehouse.id', $warehouse->id)
            ->assertJsonCount(1, 'data.items');
    });

    test('destroy removes draft adjustment', function () {
        $adjustment = StockAdjustment::factory()->create(['status' => 'draft']);

        $response = deleteJson("/api/stock-adjustments/{$adjustment->id}");

        $response->assertNoContent();
        assertDatabaseMissing('stock_adjustments', ['id' => $adjustment->id]);
    });

    test('getItems returns nested items with product and unit relations', function () {
        $adjustment = StockAdjustment::factory()->create(['status' => 'draft']);
        $product = Product::factory()->create(['name' => 'Widget A']);
        $unit = Unit::factory()->create(['name' => 'pcs']);

        StockAdjustmentItem::factory()->create([
            'stock_adjustment_id' => $adjustment->id,
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'quantity_before' => 10,
            'quantity_adjusted' => -2,
            'quantity_after' => 8,
            'unit_cost' => 100,
            'total_cost' => 200,
            'reason' => 'damaged',
        ]);

        $response = getJson("/api/stock-adjustments/{$adjustment->id}/items");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'product' => ['id', 'name'],
                        'unit' => ['id', 'name'],
                        'quantity_before',
                        'quantity_adjusted',
                        'quantity_after',
                        'unit_cost',
                        'total_cost',
                        'reason',
                    ],
                ],
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.product.name', 'Widget A')
            ->assertJsonPath('data.0.unit.name', 'pcs')
            ->assertJsonPath('data.0.reason', 'damaged');
    });

    test('getItems returns empty data array when adjustment has no items', function () {
        $adjustment = StockAdjustment::factory()->create(['status' => 'draft']);

        getJson("/api/stock-adjustments/{$adjustment->id}/items")
            ->assertOk()
            ->assertJsonPath('data', []);
    });

    test('syncItems upserts items and removes missing ones', function () {
        $adjustment = StockAdjustment::factory()->create(['status' => 'draft']);
        $unit = Unit::factory()->create();
        $existingProduct = Product::factory()->create();
        $newProduct = Product::factory()->create();

        StockAdjustmentItem::factory()->create([
            'stock_adjustment_id' => $adjustment->id,
            'product_id' => $existingProduct->id,
            'unit_id' => $unit->id,
            'quantity_before' => 10,
            'quantity_adjusted' => -2,
            'quantity_after' => 8,
        ]);

        $payload = [
            'items' => [
                [
                    'product_id' => $newProduct->id,
                    'unit_id' => $unit->id,
                    'quantity_before' => 5,
                    'quantity_adjusted' => 3,
                    'unit_cost' => 100,
                    'reason' => 'recount',
                ],
            ],
        ];

        $response = postJson("/api/stock-adjustments/{$adjustment->id}/items", $payload);

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.product.id', $newProduct->id)
            ->assertJsonPath('data.0.reason', 'recount');

        assertDatabaseHas('stock_adjustment_items', [
            'stock_adjustment_id' => $adjustment->id,
            'product_id' => $newProduct->id,
            'reason' => 'recount',
        ]);

        assertDatabaseMissing('stock_adjustment_items', [
            'stock_adjustment_id' => $adjustment->id,
            'product_id' => $existingProduct->id,
        ]);
    });

    test('syncItems validates payload', function () {
        $adjustment = StockAdjustment::factory()->create(['status' => 'draft']);

        postJson("/api/stock-adjustments/{$adjustment->id}/items", ['items' => []])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    });

    test('approving stock adjustment rolls back state when journal posting fails (non-validation)', function () {
        $adjustment = StockAdjustment::factory()->create([
            'status' => 'draft',
            'approved_at' => null,
            'approved_by' => null,
        ]);

        $this->app->bind(PostStockAdjustmentJournalAction::class, function ($app) {
            return new class($app->make(CreateJournalEntryAction::class), $app->make(ResolveControlAccountAction::class)) extends PostStockAdjustmentJournalAction
            {
                public function execute(StockAdjustment $stockAdjustment): ?JournalEntry
                {
                    throw new RuntimeException('Simulated journal posting failure');
                }
            };
        });

        putJson('/api/stock-adjustments/' . $adjustment->id, ['status' => 'approved'])
            ->assertStatus(500);

        $adjustment->refresh();
        expect($adjustment->status)->toBe('draft');
        expect($adjustment->approved_at)->toBeNull();
        expect($adjustment->approved_by)->toBeNull();
    });

    test('creating approved stock adjustment is 422 and persists nothing on journal validation failure', function () {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();
        $unit = Unit::factory()->create();

        $this->app->bind(PostStockAdjustmentJournalAction::class, function ($app) {
            return new class($app->make(CreateJournalEntryAction::class), $app->make(ResolveControlAccountAction::class)) extends PostStockAdjustmentJournalAction
            {
                public function execute(StockAdjustment $stockAdjustment): ?JournalEntry
                {
                    throw ValidationException::withMessages([
                        'coa' => 'Missing COA mapping for journal posting',
                    ]);
                }
            };
        });

        postJson('/api/stock-adjustments', [
            'warehouse_id' => $warehouse->id,
            'adjustment_date' => now()->toDateString(),
            'adjustment_type' => 'correction',
            'status' => 'approved',
            'items' => [
                [
                    'product_id' => $product->id,
                    'unit_id' => $unit->id,
                    'quantity_adjusted' => 5,
                ],
            ],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['coa']);

        expect(StockAdjustment::query()->count())->toBe(0);
        expect(StockAdjustmentItem::query()->count())->toBe(0);
        assertDatabaseMissing('stock_movements', [
            'reference_type' => StockAdjustment::class,
        ]);
    });

    test('approving stock adjustment is 422 and changes nothing when journal posting fails validation', function () {
        $adjustment = StockAdjustment::factory()->create([
            'status' => 'draft',
            'approved_at' => null,
            'approved_by' => null,
        ]);

        $this->app->bind(PostStockAdjustmentJournalAction::class, function ($app) {
            return new class($app->make(CreateJournalEntryAction::class), $app->make(ResolveControlAccountAction::class)) extends PostStockAdjustmentJournalAction
            {
                public function execute(StockAdjustment $stockAdjustment): ?JournalEntry
                {
                    throw ValidationException::withMessages([
                        'coa' => 'Missing COA mapping for journal posting',
                    ]);
                }
            };
        });

        putJson('/api/stock-adjustments/' . $adjustment->id, ['status' => 'approved'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['coa']);

        $adjustment->refresh();
        expect($adjustment->status)->toBe('draft');
        expect($adjustment->approved_at)->toBeNull();
        expect($adjustment->approved_by)->toBeNull();
        expect($adjustment->journal_entry_id)->toBeNull();
        assertDatabaseMissing('stock_movements', [
            'reference_type' => StockAdjustment::class,
            'reference_id' => $adjustment->id,
        ]);
    });
});
