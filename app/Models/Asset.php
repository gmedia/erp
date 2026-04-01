<?php

namespace App\Models;

use App\Models\Concerns\BuildsAttributeCasts;
use App\Traits\HasPipeline;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string|null $ulid
 * @property string $asset_code
 * @property string $name
 * @property int|null $asset_model_id
 * @property int $asset_category_id
 * @property string|null $serial_number
 * @property string|null $barcode
 * @property int $branch_id
 * @property int|null $asset_location_id
 * @property int|null $department_id
 * @property int|null $employee_id
 * @property int|null $supplier_id
 * @property \Illuminate\Support\Carbon $purchase_date
 * @property numeric $purchase_cost
 * @property string $currency
 * @property \Illuminate\Support\Carbon|null $warranty_end_date
 * @property string $status
 * @property string|null $condition
 * @property string|null $notes
 * @property string $depreciation_method
 * @property \Illuminate\Support\Carbon|null $depreciation_start_date
 * @property int|null $useful_life_months
 * @property numeric|null $salvage_value
 * @property numeric|null $accumulated_depreciation
 * @property numeric|null $book_value
 * @property int|null $depreciation_expense_account_id
 * @property int|null $accumulated_depr_account_id
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Account|null $accumulatedDepreciationAccount
 * @property-read \Illuminate\Database\Eloquent\Collection<int,
 *     \App\Models\PipelineEntityState> $allPipelineEntityStates
 * @property-read int|null $all_pipeline_entity_states_count
 * @property-read \App\Models\Branch $branch
 * @property-read \App\Models\AssetCategory $category
 * @property-read \App\Models\Department|null $department
 * @property-read \App\Models\Account|null $depreciationExpenseAccount
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AssetDepreciationLine> $depreciationLines
 * @property-read int|null $depreciation_lines_count
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\AssetLocation|null $location
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AssetMaintenance> $maintenances
 * @property-read int|null $maintenances_count
 * @property-read \App\Models\AssetModel|null $model
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AssetMovement> $movements
 * @property-read int|null $movements_count
 * @property-read \App\Models\PipelineEntityState|null $pipelineEntityState
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PipelineStateLog> $pipelineStateLogs
 * @property-read int|null $pipeline_state_logs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AssetStocktakeItem> $stocktakeItems
 * @property-read int|null $stocktake_items_count
 * @property-read \App\Models\Supplier|null $supplier
 *
 * @method static \Database\Factories\AssetFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereAccumulatedDeprAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereAccumulatedDepreciation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereAssetCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereAssetCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereAssetLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereAssetModelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereBarcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereBookValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereCondition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereDepreciationExpenseAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereDepreciationMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereDepreciationStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset wherePurchaseCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset wherePurchaseDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereSalvageValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereUlid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereUsefulLifeMonths($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset whereWarrantyEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Asset withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Asset extends Model
{
    use BuildsAttributeCasts, HasFactory, HasPipeline, HasUlids, SoftDeletes;

    protected $fillable = [
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

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    protected function casts(): array
    {
        return [
            ...$this->dateCasts([
                'purchase_date',
                'warranty_end_date',
                'depreciation_start_date',
            ]),
            ...$this->decimalCasts([
                'purchase_cost',
                'salvage_value',
                'accumulated_depreciation',
                'book_value',
            ]),
            ...$this->integerCasts([
                'useful_life_months',
                'asset_model_id',
                'asset_category_id',
                'branch_id',
                'asset_location_id',
                'department_id',
                'employee_id',
                'supplier_id',
                'depreciation_expense_account_id',
                'accumulated_depr_account_id',
            ]),
        ];
    }
}
