<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property int|null $useful_life_months_default
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Asset> $assets
 * @property-read int|null $assets_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AssetModel> $models
 * @property-read int|null $models_count
 *
 * @method static \Database\Factories\AssetCategoryFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetCategory whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetCategory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AssetCategory whereUsefulLifeMonthsDefault($value)
 *
 * @mixin \Eloquent
 */
class AssetCategory extends Model
{
    /** @use HasFactory<\Database\Factories\AssetCategoryFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'useful_life_months_default',
    ];

    protected $casts = [
        'useful_life_months_default' => 'integer',
    ];

    public function models(): HasMany
    {
        return $this->hasMany(AssetModel::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}
