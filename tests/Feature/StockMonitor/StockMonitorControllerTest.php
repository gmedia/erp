<?php

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('stock-monitor');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['stock_monitor']);
    $this->otherUser = createTestUserWithPermissions([]);
});

test('it requires permission to access stock monitor', function () {
    actingAs($this->otherUser)
        ->get(route('stock-monitor'))
        ->assertForbidden();

    actingAs($this->otherUser)
        ->getJson('/api/stock-monitor')
        ->assertForbidden();
});

test('it can render stock monitor page', function () {
    StockMovement::factory()->create();

    actingAs($this->user)
        ->get(route('stock-monitor'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('stock-monitor/index')
            ->has('stocks.data')
            ->has('stocks.summary')
            ->has('filterOptions.products')
            ->has('filterOptions.warehouses')
            ->has('filterOptions.branches')
            ->has('filterOptions.categories')
        );
});

test('it returns current stock snapshot per product and warehouse', function () {
    $branch = Branch::factory()->create(['name' => 'Jakarta']);
    $warehouse = Warehouse::factory()->create([
        'branch_id' => $branch->id,
        'name' => 'Gudang A',
    ]);
    $category = ProductCategory::factory()->create(['name' => 'ATK']);
    $product = Product::factory()->create([
        'category_id' => $category->id,
        'name' => 'Kertas A4',
        'code' => 'P-001',
        'cost' => 5000,
    ]);

    StockMovement::factory()->create([
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'balance_after' => 25,
        'average_cost_after' => 6000,
        'moved_at' => now()->subDay(),
    ]);

    StockMovement::factory()->create([
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'balance_after' => 10,
        'average_cost_after' => 7000,
        'moved_at' => now(),
    ]);

    actingAs($this->user)
        ->getJson('/api/stock-monitor?per_page=10')
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data', 1)
            ->where('data.0.product.name', 'Kertas A4')
            ->where('data.0.warehouse.name', 'Gudang A')
            ->where('data.0.quantity_on_hand', '10.00')
            ->where('summary.total_items', 1)
            ->where('summary.total_quantity', '10')
            ->has('meta')
            ->has('links')
        );
});

test('it can filter by branch, warehouse, category, product, and low stock threshold', function () {
    $branchA = Branch::factory()->create(['name' => 'Cabang A']);
    $branchB = Branch::factory()->create(['name' => 'Cabang B']);
    $warehouseA = Warehouse::factory()->create(['branch_id' => $branchA->id, 'name' => 'Warehouse A']);
    $warehouseB = Warehouse::factory()->create(['branch_id' => $branchB->id, 'name' => 'Warehouse B']);
    $categoryA = ProductCategory::factory()->create(['name' => 'Kategori A']);
    $categoryB = ProductCategory::factory()->create(['name' => 'Kategori B']);
    $productA = Product::factory()->create(['name' => 'Produk A', 'category_id' => $categoryA->id]);
    $productB = Product::factory()->create(['name' => 'Produk B', 'category_id' => $categoryB->id]);

    StockMovement::factory()->create([
        'product_id' => $productA->id,
        'warehouse_id' => $warehouseA->id,
        'balance_after' => 5,
    ]);

    StockMovement::factory()->create([
        'product_id' => $productB->id,
        'warehouse_id' => $warehouseB->id,
        'balance_after' => 50,
    ]);

    actingAs($this->user)
        ->getJson('/api/stock-monitor?branch_id=' . $branchA->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.warehouse.branch.id', $branchA->id);

    actingAs($this->user)
        ->getJson('/api/stock-monitor?warehouse_id=' . $warehouseA->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.warehouse.id', $warehouseA->id);

    actingAs($this->user)
        ->getJson('/api/stock-monitor?category_id=' . $categoryA->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.product.category.id', $categoryA->id);

    actingAs($this->user)
        ->getJson('/api/stock-monitor?product_id=' . $productA->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.product.id', $productA->id);

    actingAs($this->user)
        ->getJson('/api/stock-monitor?low_stock_threshold=10')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.product.id', $productA->id);
});
