<?php

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia as Assert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class)->group('book-value-depreciation-reports');

use function Pest\Laravel\actingAs;

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

test('it can render the book value & depreciation report page', function () {
    actingAs($this->user)
        ->get(route('reports.book-value-depreciation'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('reports/book-value-depreciation/index')
            ->has('assets.data', 1)
        );
})->group('book-value-depreciation-reports');

test('it can fetch book value report data via json', function () {
    actingAs($this->user)
        ->getJson(route('reports.book-value-depreciation'))
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
    actingAs($this->user)
        ->getJson(route('reports.book-value-depreciation', ['branch_id' => $this->branch->id]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.asset_code', 'IT-001');

    // Filter by category
    actingAs($this->user)
        ->getJson(route('reports.book-value-depreciation', ['asset_category_id' => $otherCategory->id]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.asset_code', 'FR-001');
})->group('book-value-depreciation-reports');

test('it can export the report data to excel', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-01 10:00:00'));
    Excel::fake();
    Storage::fake('public');

    $response = actingAs($this->user)
        ->postJson(route('reports.book-value-depreciation.export'))
        ->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');
    expect($filename)->toStartWith('book_value_depreciation_report_2026-01-01_10-00-00_');
    expect($filename)->toEndWith('.xlsx');
    
    Excel::assertStored('exports/' . $filename, 'public');
    Carbon::setTestNow();
})->group('book-value-depreciation-reports');
