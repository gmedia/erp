<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_category_id',
        'manufacturer',
        'model_name',
        'specs',
    ];

    protected $casts = [
        'asset_category_id' => 'integer',
        'specs' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}
