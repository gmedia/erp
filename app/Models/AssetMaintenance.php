<?php

namespace App\Models;

use App\Models\Concerns\BuildsAttributeCasts;
use App\Models\Concerns\HasAssetAndCreatorRelations;
use App\Models\Concerns\HasSupplierRelation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $asset_id
 * @property string $maintenance_type
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $scheduled_at
 * @property \Illuminate\Support\Carbon|null $performed_at
 * @property int|null $supplier_id
 * @property numeric $cost
 * @property string|null $notes
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Asset $asset
 * @property-read \App\Models\User|null $createdBy
 * @property-read \App\Models\Supplier|null $supplier
 *
 * @method static \Database\Factories\AssetMaintenanceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetMaintenance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetMaintenance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetMaintenance query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetMaintenance whereAssetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetMaintenance whereCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetMaintenance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetMaintenance whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetMaintenance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetMaintenance whereMaintenanceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetMaintenance whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetMaintenance wherePerformedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetMaintenance whereScheduledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetMaintenance whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetMaintenance whereSupplierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetMaintenance whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class AssetMaintenance extends Model
{
    /** @use HasFactory<\Database\Factories\AssetMaintenanceFactory> */
    use BuildsAttributeCasts, HasAssetAndCreatorRelations, HasFactory, HasSupplierRelation;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'asset_id',
        'maintenance_type',
        'status',
        'scheduled_at',
        'performed_at',
        'supplier_id',
        'cost',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            ...$this->datetimeCasts([
                'scheduled_at',
                'performed_at',
            ]),
            ...$this->decimalCasts(['cost']),
            ...$this->integerCasts([
                'asset_id',
                'supplier_id',
                'created_by',
            ]),
        ];
    }
}
