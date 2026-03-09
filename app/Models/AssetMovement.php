<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $asset_id
 * @property string $movement_type
 * @property \Illuminate\Support\Carbon $moved_at
 * @property int|null $from_branch_id
 * @property int|null $to_branch_id
 * @property int|null $from_location_id
 * @property int|null $to_location_id
 * @property int|null $from_department_id
 * @property int|null $to_department_id
 * @property int|null $from_employee_id
 * @property int|null $to_employee_id
 * @property string|null $reference
 * @property string|null $notes
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Asset $asset
 * @property-read \App\Models\User|null $createdBy
 * @property-read \App\Models\Branch|null $fromBranch
 * @property-read \App\Models\Department|null $fromDepartment
 * @property-read \App\Models\Employee|null $fromEmployee
 * @property-read \App\Models\AssetLocation|null $fromLocation
 * @property-read \App\Models\Branch|null $toBranch
 * @property-read \App\Models\Department|null $toDepartment
 * @property-read \App\Models\Employee|null $toEmployee
 * @property-read \App\Models\AssetLocation|null $toLocation
 *
 * @method static \Database\Factories\AssetMovementFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetMovement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetMovement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetMovement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetMovement whereAssetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetMovement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetMovement whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetMovement whereFromBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetMovement whereFromDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetMovement whereFromEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetMovement whereFromLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetMovement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetMovement whereMovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetMovement whereMovementType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetMovement whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetMovement whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetMovement whereToBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetMovement whereToDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetMovement whereToEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetMovement whereToLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetMovement whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class AssetMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'movement_type',
        'moved_at',
        'from_branch_id',
        'to_branch_id',
        'from_location_id',
        'to_location_id',
        'from_department_id',
        'to_department_id',
        'from_employee_id',
        'to_employee_id',
        'reference',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'moved_at' => 'datetime',
        'asset_id' => 'integer',
        'from_branch_id' => 'integer',
        'to_branch_id' => 'integer',
        'from_location_id' => 'integer',
        'to_location_id' => 'integer',
        'from_department_id' => 'integer',
        'to_department_id' => 'integer',
        'from_employee_id' => 'integer',
        'to_employee_id' => 'integer',
        'created_by' => 'integer',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function fromBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function toBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(AssetLocation::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(AssetLocation::class, 'to_location_id');
    }

    public function fromDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'from_department_id');
    }

    public function toDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'to_department_id');
    }

    public function fromEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'from_employee_id');
    }

    public function toEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'to_employee_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
