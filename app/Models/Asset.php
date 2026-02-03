<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
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

    protected $casts = [
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
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(AssetModel::class, 'asset_model_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(AssetLocation::class, 'asset_location_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function depreciationExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'depreciation_expense_account_id');
    }

    public function accumulatedDepreciationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accumulated_depr_account_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(AssetMovement::class);
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(AssetMaintenance::class);
    }

    public function stocktakeItems(): HasMany
    {
        return $this->hasMany(AssetStocktakeItem::class);
    }

    public function depreciationLines(): HasMany
    {
        return $this->hasMany(AssetDepreciationLine::class);
    }
}
