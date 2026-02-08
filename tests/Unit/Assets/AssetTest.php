<?php

use App\Models\{Asset, AssetCategory, AssetModel, Branch, AssetLocation, Department, Employee, Supplier, Account};
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('asset model has correct fillable attributes', function () {
    $asset = new Asset();
    $fillable = [
        'ulid',
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
    
    expect($asset->getFillable())->toEqual($fillable);
});

test('asset has relationships', function () {
    $asset = Asset::factory()->create();
    
    expect($asset->category)->toBeInstanceOf(AssetCategory::class);
    expect($asset->branch)->toBeInstanceOf(Branch::class);
    
    // Optional relationships
    if ($asset->asset_model_id) expect($asset->model)->toBeInstanceOf(AssetModel::class);
    if ($asset->asset_location_id) expect($asset->location)->toBeInstanceOf(AssetLocation::class);
    if ($asset->department_id) expect($asset->department)->toBeInstanceOf(Department::class);
    if ($asset->employee_id) expect($asset->employee)->toBeInstanceOf(Employee::class);
    if ($asset->supplier_id) expect($asset->supplier)->toBeInstanceOf(Supplier::class);
    if ($asset->depreciation_expense_account_id) expect($asset->depreciationExpenseAccount)->toBeInstanceOf(Account::class);
    if ($asset->accumulated_depr_account_id) expect($asset->accumulatedDeprAccount)->toBeInstanceOf(Account::class);
});
