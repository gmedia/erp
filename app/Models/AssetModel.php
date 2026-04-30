<?php

namespace App\Models;

use App\Models\Concerns\BuildsAttributeCasts;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $asset_category_id
 * @property string|null $manufacturer
 * @property string $model_name
 * @property array<array-key, mixed>|null $specs
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Asset> $assets
 * @property-read int|null $assets_count
 * @property-read \App\Models\AssetCategory $category
 *
 * @method static \Database\Factories\AssetModelFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetModel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetModel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetModel query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetModel whereAssetCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetModel whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetModel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetModel whereManufacturer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetModel whereModelName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetModel whereSpecs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetModel whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class AssetModel extends Model
{
    /** @use HasFactory<\Database\Factories\AssetModelFactory> */
    use BuildsAttributeCasts, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'asset_category_id',
        'manufacturer',
        'model_name',
        'specs',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    protected function casts(): array
    {
        return [
            ...$this->integerCasts(['asset_category_id']),
            'specs' => 'array',
        ];
    }
}
