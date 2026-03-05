<?php

use App\Models\Branch;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia as Assert;
use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('stock-adjustment-report');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['stock_adjustment_report']);
    $this->otherUser = createTestUserWithPermissions([]);
});

test('it requires permission to access stock adjustment report', function () {
    actingAs($this->otherUser)
        ->get(route('reports.stock-adjustment'))
        ->assertForbidden();
});

test('it can render stock adjustment report page', function () {
    StockAdjustmentItem::factory()->create();

    actingAs($this->user)
        ->get(route('reports.stock-adjustment'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('reports/stock-adjustment/index')
            ->has('rows.data')
        );
});

test('it can fetch stock adjustment report data via json', function () {
    $branch = Branch::factory()->create(['name' => 'HQ']);
    $warehouse = Warehouse::factory()->create([
        'branch_id' => $branch->id,
        'name' => 'Gudang A',
        'code' => 'WH-A',
    ]);

    $adjustment = StockAdjustment::factory()->create([
        'warehouse_id' => $warehouse->id,
        'adjustment_date' => '2026-03-05',
        'adjustment_type' => 'damage',
        'status' => 'approved',
    ]);

    StockAdjustmentItem::factory()->create([
        'stock_adjustment_id' => $adjustment->id,
        'quantity_adjusted' => -2,
        'total_cost' => 200,
    ]);
    StockAdjustmentItem::factory()->create([
        'stock_adjustment_id' => $adjustment->id,
        'quantity_adjusted' => 3,
        'total_cost' => 300,
    ]);

    actingAs($this->user)
        ->getJson(route('reports.stock-adjustment'))
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data', 1)
            ->where('data.0.adjustment_date', '2026-03-05')
            ->where('data.0.adjustment_type', 'damage')
            ->where('data.0.status', 'approved')
            ->where('data.0.warehouse.name', 'Gudang A')
            ->where('data.0.warehouse.branch.name', 'HQ')
            ->where('data.0.adjustment_count', 1)
            ->where('data.0.total_quantity_adjusted', '1.00')
            ->where('data.0.total_adjustment_value', '500.00')
            ->has('meta')
            ->has('links')
        );
});

test('it can filter by warehouse branch type status and date', function () {
    $branchA = Branch::factory()->create(['name' => 'Cabang A']);
    $branchB = Branch::factory()->create(['name' => 'Cabang B']);
    $warehouseA = Warehouse::factory()->create(['branch_id' => $branchA->id]);
    $warehouseB = Warehouse::factory()->create(['branch_id' => $branchB->id]);

    $adjustmentA = StockAdjustment::factory()->create([
        'warehouse_id' => $warehouseA->id,
        'adjustment_date' => '2026-03-01',
        'adjustment_type' => 'damage',
        'status' => 'approved',
    ]);
    $adjustmentB = StockAdjustment::factory()->create([
        'warehouse_id' => $warehouseB->id,
        'adjustment_date' => '2026-03-10',
        'adjustment_type' => 'expired',
        'status' => 'draft',
    ]);

    StockAdjustmentItem::factory()->create(['stock_adjustment_id' => $adjustmentA->id]);
    StockAdjustmentItem::factory()->create(['stock_adjustment_id' => $adjustmentB->id]);

    actingAs($this->user)
        ->getJson(route('reports.stock-adjustment', ['warehouse_id' => $warehouseA->id]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.warehouse.id', $warehouseA->id);

    actingAs($this->user)
        ->getJson(route('reports.stock-adjustment', ['branch_id' => $branchA->id]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.warehouse.branch.id', $branchA->id);

    actingAs($this->user)
        ->getJson(route('reports.stock-adjustment', ['adjustment_type' => 'damage']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.adjustment_type', 'damage');

    actingAs($this->user)
        ->getJson(route('reports.stock-adjustment', ['status' => 'draft']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'draft');

    actingAs($this->user)
        ->getJson(route('reports.stock-adjustment', ['start_date' => '2026-03-05', 'end_date' => '2026-03-31']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.adjustment_date', '2026-03-10');
});

test('it can export stock adjustment report', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-04 12:00:00'));
    Excel::fake();
    Storage::fake('public');

    $response = actingAs($this->user)
        ->postJson(route('reports.stock-adjustment.export'))
        ->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');
    expect($filename)->toStartWith('stock_adjustment_report_2026-03-04_12-00-00_');
    expect($filename)->toEndWith('.xlsx');

    Excel::assertStored('exports/' . $filename, 'public');
    Carbon::setTestNow();
});
