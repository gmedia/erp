<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetStocktakeItem extends Model
{
    use HasFactory;

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
