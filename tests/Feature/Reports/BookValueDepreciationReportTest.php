<?php

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class)->group('book-value-depreciation-reports');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['asset']);

    $this->category = AssetCategory::factory()->create(['name' => 'IT Equipment']);
    $this->branch = Branch::factory()->create(['name' => 'HQ']);

    $this->asset = Asset::factory()->create([
        'asset_code' => 'IT-001',
        'name' => 'Server X',
        'asset_category_id' => $this->category->id,
        'branch_id' => $this->branch->id,
        'status' => 'active',
        'purchase_cost' => 10000,
        'salvage_value' => 1000,
        'useful_life_months' => 48,
        'accumulated_depreciation' => 2000,
        'book_value' => 8000,
    ]);
});

test('it can fetch book value report data via json', function () {
    Sanctum::actingAs($this->user, ['*']);
    $this->getJson('/api/reports/book-value-depreciation')
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data', 1)
            ->has('data.0', fn ($json) => $json
                ->where('asset_code', 'IT-001')
                ->where('name', 'Server X')
                ->where('category_name', 'IT Equipment')
                ->where('branch_name', 'HQ')
                ->where('purchase_cost', 10000)
                ->where('salvage_value', 1000)
                ->where('useful_life_months', 48)
                ->where('accumulated_depreciation', 2000)
                ->where('book_value', 8000)
                ->etc()
            )
            ->has('meta')
            ->has('links')
        );
})->group('book-value-depreciation-reports');

test('it can filter the report by category and branch', function () {
    // Create another asset that won't match the filter
    $otherCategory = AssetCategory::factory()->create(['name' => 'Furniture']);
    $otherBranch = Branch::factory()->create(['name' => 'Branch B']);
    Asset::factory()->create([
        'asset_code' => 'FR-001',
        'name' => 'Desk',
        'asset_category_id' => $otherCategory->id,
        'branch_id' => $otherBranch->id,
        'status' => 'active',
    ]);

    // Filter by branch
    Sanctum::actingAs($this->user, ['*']);
    $this->getJson('/api/reports/book-value-depreciation?branch_id=' . $this->branch->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.asset_code', 'IT-001');

    // Filter by category
    Sanctum::actingAs($this->user, ['*']);
    $this->getJson('/api/reports/book-value-depreciation?asset_category_id=' . $otherCategory->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.asset_code', 'FR-001');
})->group('book-value-depreciation-reports');

test('it can export the report data to excel', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-01 10:00:00'));
    Excel::fake();
    Storage::fake('public');

    Sanctum::actingAs($this->user, ['*']);
    $response = $this->postJson('/api/reports/book-value-depreciation/export')
        ->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');
    expect($filename)->toStartWith('book_value_depreciation_report_2026-01-01_10-00-00_');
    expect($filename)->toEndWith('.xlsx');

    Excel::assertStored('exports/' . $filename, 'public');
    Carbon::setTestNow();
})->group('book-value-depreciation-reports');
