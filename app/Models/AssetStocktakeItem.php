<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $asset_stocktake_id
 * @property int $asset_id
 * @property int|null $expected_branch_id
 * @property int|null $expected_location_id
 * @property int|null $found_branch_id
 * @property int|null $found_location_id
 * @property string $result
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $checked_at
 * @property int|null $checked_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Asset $asset
 * @property-read \App\Models\User|null $checkedBy
 * @property-read \App\Models\Branch|null $expectedBranch
 * @property-read \App\Models\AssetLocation|null $expectedLocation
 * @property-read \App\Models\Branch|null $foundBranch
 * @property-read \App\Models\AssetLocation|null $foundLocation
 * @property-read \App\Models\AssetStocktake $stocktake
 *
 * @method static \Database\Factories\AssetStocktakeItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetStocktakeItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetStocktakeItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetStocktakeItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetStocktakeItem whereAssetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetStocktakeItem whereAssetStocktakeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetStocktakeItem whereCheckedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetStocktakeItem whereCheckedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetStocktakeItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetStocktakeItem whereExpectedBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetStocktakeItem whereExpectedLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetStocktakeItem whereFoundBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetStocktakeItem whereFoundLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetStocktakeItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetStocktakeItem whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetStocktakeItem whereResult($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetStocktakeItem whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class AssetStocktakeItem extends Model
{
    /** @use HasFactory<\Database\Factories\AssetStocktakeItemFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'asset_stocktake_id',
        'asset_id',
        'expected_branch_id',
        'expected_location_id',
        'found_branch_id',
        'found_location_id',
        'result',
        'notes',
        'checked_at',
        'checked_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'checked_at' => 'datetime',
        'asset_stocktake_id' => 'integer',
        'asset_id' => 'integer',
        'expected_branch_id' => 'integer',
        'expected_location_id' => 'integer',
        'found_branch_id' => 'integer',
        'found_location_id' => 'integer',
        'checked_by' => 'integer',
    ];

    public function stocktake(): BelongsTo
    {
        return $this->belongsTo(AssetStocktake::class, 'asset_stocktake_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function expectedBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'expected_branch_id');
    }

    public function expectedLocation(): BelongsTo
    {
        return $this->belongsTo(AssetLocation::class, 'expected_location_id');
    }

    public function foundBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'found_branch_id');
    }

    public function foundLocation(): BelongsTo
    {
        return $this->belongsTo(AssetLocation::class, 'found_location_id');
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
