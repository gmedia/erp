<?php

namespace Tests\Unit;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetLocation;
use App\Models\AssetModel;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Supplier;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('assets', 'asset-unit');

test('asset model has correct fillable attributes', function () {
    $fillable = [
        'asset_code',
        'name',
        'asset_model_id',
        'asset_category_id',
        'serial_number',
        'barcode',
        'branch_id',
        'asset_location_id',
        'department_id',
        'employee_id',
        'supplier_id',
        'purchase_date',
        'purchase_cost',
        'currency',
        'warranty_end_date',
        'status',
        'condition',
        'notes',
        'depreciation_method',
        'depreciation_start_date',
        'useful_life_months',
        'salvage_value',
        'accumulated_depreciation',
        'book_value',
        'depreciation_expense_account_id',
        'accumulated_depr_account_id',
    ];

    expect((new Asset())->getFillable())->toEqual($fillable);
});

test('asset model has correct casts', function () {
    $casts = [
        'id' => 'int',
        'purchase_date' => 'date',
        'warranty_end_date' => 'date',
        'purchase_cost' => 'decimal:2',
        'salvage_value' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'book_value' => 'decimal:2',
        'depreciation_start_date' => 'date',
        'useful_life_months' => 'integer',
        'asset_model_id' => 'integer',
        'asset_category_id' => 'integer',
        'branch_id' => 'integer',
        'asset_location_id' => 'integer',
        'department_id' => 'integer',
        'employee_id' => 'integer',
        'supplier_id' => 'integer',
        'depreciation_expense_account_id' => 'integer',
        'accumulated_depr_account_id' => 'integer',
        'deleted_at' => 'datetime',
    ];

    $asset = new Asset();
    
    // Note: getCasts() might include more than what we defined manually (like id)
    $actualCasts = $asset->getCasts();
    
    foreach ($casts as $key => $type) {
        expect($actualCasts)->toHaveKey($key, $type);
    }
});

test('asset belongs to a category', function () {
    $asset = Asset::factory()->create();
    expect($asset->category)->toBeInstanceOf(AssetCategory::class);
});

test('asset belongs to a model', function () {
    $asset = Asset::factory()->create([
        'asset_model_id' => AssetModel::factory()->create()->id
    ]);
    expect($asset->model)->toBeInstanceOf(AssetModel::class);
});

test('asset belongs to a branch', function () {
    $asset = Asset::factory()->create();
    expect($asset->branch)->toBeInstanceOf(Branch::class);
});

test('asset belongs to a location', function () {
    $asset = Asset::factory()->create([
        'asset_location_id' => AssetLocation::factory()->create()->id
    ]);
    expect($asset->location)->toBeInstanceOf(AssetLocation::class);
});

test('asset belongs to a department', function () {
    $asset = Asset::factory()->create([
        'department_id' => Department::factory()->create()->id
    ]);
    expect($asset->department)->toBeInstanceOf(Department::class);
});

test('asset belongs to an employee', function () {
    $asset = Asset::factory()->create([
        'employee_id' => Employee::factory()->create()->id
    ]);
    expect($asset->employee)->toBeInstanceOf(Employee::class);
});

test('asset belongs to a supplier', function () {
    $asset = Asset::factory()->create([
        'supplier_id' => Supplier::factory()->create()->id
    ]);
    expect($asset->supplier)->toBeInstanceOf(Supplier::class);
});

test('asset belongs to depreciation expense account', function () {
    $asset = Asset::factory()->create([
        'depreciation_expense_account_id' => Account::factory()->create()->id
    ]);
    expect($asset->depreciationExpenseAccount)->toBeInstanceOf(Account::class);
});

test('asset belongs to accumulated depreciation account', function () {
    $asset = Asset::factory()->create([
        'accumulated_depr_account_id' => Account::factory()->create()->id
    ]);
    expect($asset->accumulatedDepreciationAccount)->toBeInstanceOf(Account::class);
});

test('asset has movements', function () {
    $asset = Asset::factory()->create();
    expect($asset->movements)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
});

test('asset has maintenances', function () {
    $asset = Asset::factory()->create();
    expect($asset->maintenances)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
});

test('asset has stocktake items', function () {
    $asset = Asset::factory()->create();
    expect($asset->stocktakeItems)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
});

test('asset has depreciation lines', function () {
    $asset = Asset::factory()->create();
    expect($asset->depreciationLines)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
});
