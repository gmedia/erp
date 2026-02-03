<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
