<?php

use App\Models\Branch;
use App\Models\InventoryStocktake;
use App\Models\InventoryStocktakeItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia as Assert;
use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('inventory-stocktake-variance-report');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['inventory_stocktake_variance_report']);
    $this->otherUser = createTestUserWithPermissions([]);
});

test('it requires permission to access inventory stocktake variance report', function () {
    actingAs($this->otherUser)
        ->get(route('reports.inventory-stocktake-variance'))
        ->assertForbidden();
});

test('it can render inventory stocktake variance report page', function () {
    InventoryStocktakeItem::factory()->create([
        'variance' => 2,
        'result' => 'surplus',
    ]);

    actingAs($this->user)
        ->get(route('reports.inventory-stocktake-variance'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('reports/inventory-stocktake-variance/index')
            ->has('rows.data')
        );
});

test('it can fetch inventory stocktake variance report data via json', function () {
    $branch = Branch::factory()->create(['name' => 'HQ']);
    $warehouse = Warehouse::factory()->create(['branch_id' => $branch->id, 'name' => 'Gudang A', 'code' => 'WH-A']);
    $category = ProductCategory::factory()->create(['name' => 'ATK']);
    $unit = Unit::factory()->create(['name' => 'PCS']);
    $product = Product::factory()->create([
        'name' => 'Kertas A4',
        'code' => 'P-001',
        'category_id' => $category->id,
        'unit_id' => $unit->id,
    ]);
    $stocktake = InventoryStocktake::factory()->create([
        'warehouse_id' => $warehouse->id,
        'stocktake_number' => 'STK-0001',
        'stocktake_date' => '2026-03-04',
    ]);

    InventoryStocktakeItem::factory()->create([
        'inventory_stocktake_id' => $stocktake->id,
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'system_quantity' => 10,
        'counted_quantity' => 13,
        'variance' => 3,
        'result' => 'surplus',
        'counted_by' => $this->user->id,
        'counted_at' => now(),
    ]);

    actingAs($this->user)
        ->getJson(route('reports.inventory-stocktake-variance'))
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data', 1)
            ->where('data.0.stocktake.stocktake_number', 'STK-0001')
            ->where('data.0.product.name', 'Kertas A4')
            ->where('data.0.product.category.name', 'ATK')
            ->where('data.0.warehouse.name', 'Gudang A')
            ->where('data.0.warehouse.branch.name', 'HQ')
            ->where('data.0.system_quantity', '10.00')
            ->where('data.0.counted_quantity', '13.00')
            ->where('data.0.variance', '3.00')
            ->where('data.0.result', 'surplus')
            ->has('meta')
            ->has('links')
        );
});

test('it can filter by dimensions and result', function () {
    $branchA = Branch::factory()->create(['name' => 'Cabang A']);
    $branchB = Branch::factory()->create(['name' => 'Cabang B']);
    $warehouseA = Warehouse::factory()->create(['branch_id' => $branchA->id]);
    $warehouseB = Warehouse::factory()->create(['branch_id' => $branchB->id]);
    $categoryA = ProductCategory::factory()->create(['name' => 'Kategori A']);
    $categoryB = ProductCategory::factory()->create(['name' => 'Kategori B']);
    $unit = Unit::factory()->create();
    $productA = Product::factory()->create(['category_id' => $categoryA->id, 'unit_id' => $unit->id, 'code' => 'PA-01']);
    $productB = Product::factory()->create(['category_id' => $categoryB->id, 'unit_id' => $unit->id, 'code' => 'PB-01']);
    $stocktakeA = InventoryStocktake::factory()->create(['warehouse_id' => $warehouseA->id, 'stocktake_date' => '2026-03-01']);
    $stocktakeB = InventoryStocktake::factory()->create(['warehouse_id' => $warehouseB->id, 'stocktake_date' => '2026-03-10']);

    InventoryStocktakeItem::factory()->create([
        'inventory_stocktake_id' => $stocktakeA->id,
        'product_id' => $productA->id,
        'unit_id' => $unit->id,
        'variance' => 2,
        'result' => 'surplus',
    ]);
    InventoryStocktakeItem::factory()->create([
        'inventory_stocktake_id' => $stocktakeB->id,
        'product_id' => $productB->id,
        'unit_id' => $unit->id,
        'variance' => -1,
        'result' => 'deficit',
    ]);

    actingAs($this->user)
        ->getJson(route('reports.inventory-stocktake-variance', ['inventory_stocktake_id' => $stocktakeA->id]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.stocktake.id', $stocktakeA->id);

    actingAs($this->user)
        ->getJson(route('reports.inventory-stocktake-variance', ['product_id' => $productA->id]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.product.id', $productA->id);

    actingAs($this->user)
        ->getJson(route('reports.inventory-stocktake-variance', ['warehouse_id' => $warehouseA->id]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.warehouse.id', $warehouseA->id);

    actingAs($this->user)
        ->getJson(route('reports.inventory-stocktake-variance', ['branch_id' => $branchA->id]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.warehouse.branch.id', $branchA->id);

    actingAs($this->user)
        ->getJson(route('reports.inventory-stocktake-variance', ['category_id' => $categoryA->id]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.product.category.id', $categoryA->id);

    actingAs($this->user)
        ->getJson(route('reports.inventory-stocktake-variance', ['result' => 'deficit']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.result', 'deficit');

    actingAs($this->user)
        ->getJson(route('reports.inventory-stocktake-variance', ['start_date' => '2026-03-05', 'end_date' => '2026-03-31']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.stocktake.id', $stocktakeB->id);
});

test('it can export inventory stocktake variance report', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-04 11:00:00'));
    Excel::fake();
    Storage::fake('public');

    $response = actingAs($this->user)
        ->postJson(route('reports.inventory-stocktake-variance.export'))
        ->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');
    expect($filename)->toStartWith('inventory_stocktake_variance_report_2026-03-04_11-00-00_');
    expect($filename)->toEndWith('.xlsx');

    Excel::assertStored('exports/' . $filename, 'public');
    Carbon::setTestNow();
});
