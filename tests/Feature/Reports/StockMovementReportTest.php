<?php

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia as Assert;
use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('stock-movement-report');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['stock_movement_report']);
    $this->otherUser = createTestUserWithPermissions([]);
});

test('it requires permission to access stock movement report', function () {
    \Laravel\Sanctum\Sanctum::actingAs($this->otherUser, ['*']);
    $this->getJson('/api/reports/stock-movement')
        ->assertForbidden();
});



test('it can fetch aggregated stock movement report data', function () {
    $branch = Branch::factory()->create(['name' => 'HQ']);
    $warehouse = Warehouse::factory()->create([
        'branch_id' => $branch->id,
        'name' => 'Gudang A',
        'code' => 'WH-A',
    ]);
    $category = ProductCategory::factory()->create(['name' => 'ATK']);
    $product = Product::factory()->create([
        'name' => 'Kertas A4',
        'code' => 'P-001',
        'category_id' => $category->id,
    ]);

    StockMovement::factory()->create([
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'quantity_in' => 10,
        'quantity_out' => 0,
        'balance_after' => 10,
        'moved_at' => now()->subDay(),
    ]);

    StockMovement::factory()->create([
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'quantity_in' => 5,
        'quantity_out' => 2,
        'balance_after' => 13,
        'moved_at' => now(),
    ]);

    \Laravel\Sanctum\Sanctum::actingAs($this->user, ['*']);
    $this->getJson('/api/reports/stock-movement')
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data', 1)
            ->where('data.0.product.name', 'Kertas A4')
            ->where('data.0.product.category.name', 'ATK')
            ->where('data.0.warehouse.name', 'Gudang A')
            ->where('data.0.warehouse.branch.name', 'HQ')
            ->where('data.0.total_in', '15.00')
            ->where('data.0.total_out', '2.00')
            ->where('data.0.ending_balance', '13.00')
            ->has('meta')
            ->has('links')
        );
});

test('it can filter by date and dimensions', function () {
    $branchA = Branch::factory()->create(['name' => 'Cabang A']);
    $branchB = Branch::factory()->create(['name' => 'Cabang B']);
    $warehouseA = Warehouse::factory()->create(['branch_id' => $branchA->id, 'name' => 'Warehouse A']);
    $warehouseB = Warehouse::factory()->create(['branch_id' => $branchB->id, 'name' => 'Warehouse B']);
    $categoryA = ProductCategory::factory()->create(['name' => 'Kategori A']);
    $categoryB = ProductCategory::factory()->create(['name' => 'Kategori B']);
    $productA = Product::factory()->create(['name' => 'Produk A', 'code' => 'PA-01', 'category_id' => $categoryA->id]);
    $productB = Product::factory()->create(['name' => 'Produk B', 'code' => 'PB-01', 'category_id' => $categoryB->id]);

    StockMovement::factory()->create([
        'product_id' => $productA->id,
        'warehouse_id' => $warehouseA->id,
        'quantity_in' => 5,
        'balance_after' => 5,
        'moved_at' => '2026-03-01 10:00:00',
    ]);

    StockMovement::factory()->create([
        'product_id' => $productB->id,
        'warehouse_id' => $warehouseB->id,
        'quantity_in' => 7,
        'balance_after' => 7,
        'moved_at' => '2026-03-10 10:00:00',
    ]);

    \Laravel\Sanctum\Sanctum::actingAs($this->user, ['*']);
    $this->getJson('/api/reports/stock-movement?product_id=' . $productA->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.product.id', $productA->id);

    \Laravel\Sanctum\Sanctum::actingAs($this->user, ['*']);
    $this->getJson('/api/reports/stock-movement?warehouse_id=' . $warehouseA->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.warehouse.id', $warehouseA->id);

    \Laravel\Sanctum\Sanctum::actingAs($this->user, ['*']);
    $this->getJson('/api/reports/stock-movement?branch_id=' . $branchA->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.warehouse.branch.id', $branchA->id);

    \Laravel\Sanctum\Sanctum::actingAs($this->user, ['*']);
    $this->getJson('/api/reports/stock-movement?category_id=' . $categoryA->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.product.category.id', $categoryA->id);

    \Laravel\Sanctum\Sanctum::actingAs($this->user, ['*']);
    $this->getJson('/api/reports/stock-movement?start_date=2026-03-05&end_date=2026-03-31')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.product.id', $productB->id);
});

test('it accepts category sort alias from datatable', function () {
    StockMovement::factory()->create();

    \Laravel\Sanctum\Sanctum::actingAs($this->user, ['*']);
    $this->getJson('/api/reports/stock-movement?sort_by=product_category_name&sort_direction=asc')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta', 'links']);
});

test('it can export stock movement report', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-04 10:00:00'));
    Excel::fake();
    Storage::fake('public');

    \Laravel\Sanctum\Sanctum::actingAs($this->user, ['*']);
    $response = $this->postJson('/api/reports/stock-movement/export')
        ->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');
    expect($filename)->toStartWith('stock_movement_report_2026-03-04_10-00-00_');
    expect($filename)->toEndWith('.xlsx');

    Excel::assertStored('exports/' . $filename, 'public');
    Carbon::setTestNow();
});
