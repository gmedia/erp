<?php

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetMaintenance;
use App\Models\Branch;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class)->group('assets');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['asset']);

    $this->category = AssetCategory::factory()->create(['name' => 'IT Equipment']);
    $this->branch = Branch::factory()->create(['name' => 'HQ']);
    $this->supplier = Supplier::factory()->create(['name' => 'IT Vendor Inc']);

    $this->asset = Asset::factory()->create([
        'asset_code' => 'IT-001',
        'name' => 'Server X',
        'asset_category_id' => $this->category->id,
        'branch_id' => $this->branch->id,
        'status' => 'active',
    ]);

    $this->maintenance = AssetMaintenance::create([
        'asset_id' => $this->asset->id,
        'maintenance_type' => 'preventive',
        'status' => 'completed',
        'scheduled_at' => now()->subDays(5),
        'performed_at' => now()->subDays(2),
        'supplier_id' => $this->supplier->id,
        'cost' => 500.00,
        'notes' => 'Regular server cleaning',
        'created_by' => $this->user->id,
    ]);
});

test('it can fetch maintenance cost report data via json', function () {
    Sanctum::actingAs($this->user, ['*']);
    $this->getJson('/api/reports/maintenance-cost')
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) => $json
                ->has('data', 1)
                ->has(
                    'data.0',
                    fn ($json) => $json
                        ->where('asset_code', 'IT-001')
                        ->where('asset_name', 'Server X')
                        ->where('category_name', 'IT Equipment')
                        ->where('branch_name', 'HQ')
                        ->where('maintenance_type', 'preventive')
                        ->where('status', 'completed')
                        ->has('scheduled_at')
                        ->has('performed_at')
                        ->where('supplier_name', 'IT Vendor Inc')
                        ->where('cost', 500)
                        ->where('notes', 'Regular server cleaning')
                        ->etc(),
                )
                ->has('meta')
                ->has('links')
        );
});

test('it can filter the report by various parameters', function () {
    // Modify maintenance to be older
    $this->maintenance->update(['performed_at' => '2025-01-01']);

    // Create another maintenance record
    $otherCategory = AssetCategory::factory()->create(['name' => 'Furniture']);
    $otherBranch = Branch::factory()->create(['name' => 'Branch B']);
    $otherSupplier = Supplier::factory()->create(['name' => 'FixIt Corp']);

    $otherAsset = Asset::factory()->create([
        'asset_code' => 'FR-001',
        'name' => 'Desk',
        'asset_category_id' => $otherCategory->id,
        'branch_id' => $otherBranch->id,
        'status' => 'active',
    ]);

    AssetMaintenance::create([
        'asset_id' => $otherAsset->id,
        'maintenance_type' => 'corrective',
        'status' => 'completed',
        'scheduled_at' => now()->subDays(1),
        'performed_at' => now(),
        'supplier_id' => $otherSupplier->id,
        'cost' => 150.00,
        'notes' => 'Fixed broken leg',
        'created_by' => $this->user->id,
    ]);

    // Filter by branch
    Sanctum::actingAs($this->user, ['*']);
    $this->getJson('/api/reports/maintenance-cost?branch_id=' . $this->branch->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.asset_code', 'IT-001');

    // Filter by category
    Sanctum::actingAs($this->user, ['*']);
    $this->getJson('/api/reports/maintenance-cost?asset_category_id=' . $otherCategory->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.asset_code', 'FR-001');

    // Filter by supplier
    Sanctum::actingAs($this->user, ['*']);
    $this->getJson('/api/reports/maintenance-cost?supplier_id=' . $otherSupplier->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.asset_code', 'FR-001');

    // Filter by type
    Sanctum::actingAs($this->user, ['*']);
    $this->getJson('/api/reports/maintenance-cost?maintenance_type=preventive')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.asset_code', 'IT-001');

    // Filter by date range
    Sanctum::actingAs($this->user, ['*']);
    $this->getJson('/api/reports/maintenance-cost?start_date=2025-12-01&end_date=2026-12-31')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.asset_code', 'FR-001'); // IT-001 performed_at is 2025-01-01
});

test('it can export the report data to excel', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-01 10:00:00'));
    Excel::fake();
    Storage::fake('public');

    Sanctum::actingAs($this->user, ['*']);
    $response = $this->postJson('/api/reports/maintenance-cost/export')
        ->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');
    expect($filename)->toStartWith('maintenance_cost_report_2026-02-01_10-00-00_');
    expect($filename)->toEndWith('.xlsx');

    Excel::assertStored('exports/' . $filename, 'public');
    Carbon::setTestNow();
});
