<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $asset_depreciation_run_id
 * @property int $asset_id
 * @property numeric $amount
 * @property numeric $accumulated_before
 * @property numeric $accumulated_after
 * @property numeric $book_value_after
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Asset $asset
 * @property-read \App\Models\AssetDepreciationRun $run
 *
 * @method static \Database\Factories\AssetDepreciationLineFactory factory($count = null, $state = [])
 * @method static Builder<static>|AssetDepreciationLine newModelQuery()
 * @method static Builder<static>|AssetDepreciationLine newQuery()
 * @method static Builder<static>|AssetDepreciationLine query()
 * @method static Builder<static>|AssetDepreciationLine whereAccumulatedAfter($value)
 * @method static Builder<static>|AssetDepreciationLine whereAccumulatedBefore($value)
 * @method static Builder<static>|AssetDepreciationLine whereAmount($value)
 * @method static Builder<static>|AssetDepreciationLine whereAssetDepreciationRunId($value)
 * @method static Builder<static>|AssetDepreciationLine whereAssetId($value)
 * @method static Builder<static>|AssetDepreciationLine whereBookValueAfter($value)
 * @method static Builder<static>|AssetDepreciationLine whereCreatedAt($value)
 * @method static Builder<static>|AssetDepreciationLine whereId($value)
 * @method static Builder<static>|AssetDepreciationLine whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class AssetDepreciationLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_depreciation_run_id',
        'asset_id',
        'amount',
        'accumulated_before',
        'accumulated_after',
        'book_value_after',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'accumulated_before' => 'decimal:2',
        'accumulated_after' => 'decimal:2',
        'book_value_after' => 'decimal:2',
        'asset_depreciation_run_id' => 'integer',
        'asset_id' => 'integer',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(AssetDepreciationRun::class, 'asset_depreciation_run_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
