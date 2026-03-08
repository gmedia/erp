<?php

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia as Assert;
use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('inventory-valuation-report');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['inventory_valuation_report']);
    $this->otherUser = createTestUserWithPermissions([]);
});

test('it requires permission to access inventory valuation report', function () {
    \Laravel\Sanctum\Sanctum::actingAs($this->otherUser, ['*']);
    $this->getJson('/api/reports/inventory-valuation')
        ->assertForbidden();
});



test('it can fetch inventory valuation data via json', function () {
    $branch = Branch::factory()->create(['name' => 'HQ']);
    $warehouse = Warehouse::factory()->create([
        'branch_id' => $branch->id,
        'name' => 'Gudang Utama',
        'code' => 'WH-01',
    ]);
    $category = ProductCategory::factory()->create(['name' => 'ATK']);
    $unit = Unit::factory()->create(['name' => 'PCS']);
    $product = Product::factory()->create([
        'name' => 'Kertas A4',
        'code' => 'P-001',
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'cost' => 5000,
    ]);

    StockMovement::factory()->create([
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'balance_after' => 10,
        'average_cost_after' => 6000,
    ]);

    \Laravel\Sanctum\Sanctum::actingAs($this->user, ['*']);
    $this->getJson('/api/reports/inventory-valuation')
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data', 1)
            ->where('data.0.product.name', 'Kertas A4')
            ->where('data.0.product.category.name', 'ATK')
            ->where('data.0.product.unit.name', 'PCS')
            ->where('data.0.warehouse.name', 'Gudang Utama')
            ->where('data.0.warehouse.branch.name', 'HQ')
            ->where('data.0.quantity_on_hand', '10.00')
            ->where('data.0.average_cost', '6000.00')
            ->where('data.0.stock_value', '60000.00')
            ->has('meta')
            ->has('links')
        );
});

test('it can filter by product, warehouse, branch, category and search', function () {
    $branchA = Branch::factory()->create(['name' => 'Cabang A']);
    $branchB = Branch::factory()->create(['name' => 'Cabang B']);
    $warehouseA = Warehouse::factory()->create(['branch_id' => $branchA->id, 'name' => 'Warehouse A']);
    $warehouseB = Warehouse::factory()->create(['branch_id' => $branchB->id, 'name' => 'Warehouse B']);
    $categoryA = ProductCategory::factory()->create(['name' => 'Kategori A']);
    $categoryB = ProductCategory::factory()->create(['name' => 'Kategori B']);
    $unit = Unit::factory()->create(['name' => 'PCS']);
    $productA = Product::factory()->create(['name' => 'Produk A', 'code' => 'PA-01', 'category_id' => $categoryA->id, 'unit_id' => $unit->id]);
    $productB = Product::factory()->create(['name' => 'Produk B', 'code' => 'PB-01', 'category_id' => $categoryB->id, 'unit_id' => $unit->id]);

    StockMovement::factory()->create([
        'product_id' => $productA->id,
        'warehouse_id' => $warehouseA->id,
        'balance_after' => 5,
    ]);

    StockMovement::factory()->create([
        'product_id' => $productB->id,
        'warehouse_id' => $warehouseB->id,
        'balance_after' => 15,
    ]);

    \Laravel\Sanctum\Sanctum::actingAs($this->user, ['*']);
    $this->getJson('/api/reports/inventory-valuation?product_id=' . $productA->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.product.id', $productA->id);

    \Laravel\Sanctum\Sanctum::actingAs($this->user, ['*']);
    $this->getJson('/api/reports/inventory-valuation?warehouse_id=' . $warehouseA->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.warehouse.id', $warehouseA->id);

    \Laravel\Sanctum\Sanctum::actingAs($this->user, ['*']);
    $this->getJson('/api/reports/inventory-valuation?branch_id=' . $branchA->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.warehouse.branch.id', $branchA->id);

    \Laravel\Sanctum\Sanctum::actingAs($this->user, ['*']);
    $this->getJson('/api/reports/inventory-valuation?category_id=' . $categoryA->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.product.category.id', $categoryA->id);

    \Laravel\Sanctum\Sanctum::actingAs($this->user, ['*']);
    $this->getJson('/api/reports/inventory-valuation?search=PA-01')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.product.code', 'PA-01');
});

test('it can export inventory valuation report', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-04 10:00:00'));
    Excel::fake();
    Storage::fake('public');

    \Laravel\Sanctum\Sanctum::actingAs($this->user, ['*']);
    $response = $this->postJson('/api/reports/inventory-valuation/export')
        ->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');
    expect($filename)->toStartWith('inventory_valuation_report_2026-03-04_10-00-00_');
    expect($filename)->toEndWith('.xlsx');

    Excel::assertStored('exports/' . $filename, 'public');
    Carbon::setTestNow();
});
